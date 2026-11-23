<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy};
use App\Domain\Workflow;

final class OrderService
{
    private const ACTIVE = ['PAYMENT_CONFIRMED', 'AWAITING_GARMENT', 'GARMENT_RECEIVED', 'PHYSICAL_INSPECTION', 'DESIGN_FINALIZATION',
        'IN_PRODUCTION', 'QUALITY_CONTROL', 'REWORK_REQUIRED', 'READY_TO_SHIP', 'SHIPPED'];

    public function __construct(private Db $db) {}

    public function loadForActor(Actor $a, string $id): array
    {
        $o = $this->db->one('SELECT * FROM orders WHERE id = ?', [Validate::uuid($id)]);
        if (!$o || !Policy::canReadOrder($this->db, $a, $o)) throw DomainError::notFound();
        return $o;
    }

    public function detail(Actor $a, string $id): array
    {
        $o = $this->loadForActor($a, $id);
        return [
            'order' => $o,
            'actors' => Policy::orderActors($this->db, $a, $o),
            'title' => $this->db->value('SELECT title FROM design_requests WHERE id = ?', [$o['request_id']]),
            'items' => $this->db->all('SELECT * FROM order_items WHERE order_id = ?', [$o['id']]),
            'history' => $this->db->all('SELECT to_status, note, created_at FROM order_status_history WHERE order_id = ? ORDER BY created_at', [$o['id']]),
            'payments' => $this->db->all('SELECT id, provider, provider_ref, amount_satang, direction, created_at FROM payments WHERE order_id = ? ORDER BY created_at', [$o['id']]),
            'paid' => (int) $this->db->value("SELECT COALESCE(SUM(CASE WHEN direction = 'charge' THEN amount_satang ELSE -amount_satang END), 0) FROM payments WHERE order_id = ?", [$o['id']]),
        ];
    }

    public function listForAdmin(Actor $a, ?string $status): array
    {
        Policy::requireAdmin($a);
        $sql = 'SELECT o.id, o.order_no, o.status, o.total_satang, o.created_at, r.title FROM orders o JOIN design_requests r ON r.id = o.request_id';
        $p = [];
        if ($status !== null && in_array($status, Workflow::ORDER_STATUSES, true)) { $sql .= ' WHERE o.status = ?'; $p[] = $status; }
        return $this->db->all($sql . ' ORDER BY o.created_at DESC LIMIT 200', $p);
    }

    public function transition(Actor $a, string $id, string $to, ?string $note = null): void
    {
        $note = Validate::optText($note ?? '', 'note', 2000);
        $this->db->tx(function (Db $db) use ($a, $id, $to, $note) {
            $o = $this->lock($id);
            if (!Policy::canReadOrder($db, $a, $o)) throw DomainError::notFound();
            $actors = Policy::orderActors($db, $a, $o);
            if (!$actors || !$db->value('SELECT 1 FROM order_transitions WHERE from_status = ? AND to_status = ? AND actor IN (' . Db::in($actors) . ')',
                [$o['status'], $to, ...$actors])) {
                throw DomainError::forbidden("not allowed: {$o['status']} -> {$to}");
            }
            $this->move($a, $o, $to, $note);
        });
    }

    /** Admin records a verified bank transfer. Payment rows are append-only (DB triggers). */
    public function recordManualPayment(Actor $a, string $id, int $amountSatang, string $reference): string
    {
        Policy::requireAdmin($a);
        $reference = Validate::text($reference, 'reference', 200, 3);
        if ($amountSatang <= 0 || $amountSatang > 1_000_000_000) throw DomainError::invalid('invalid amount');
        return $this->db->tx(function (Db $db) use ($a, $id, $amountSatang, $reference) {
            $o = $this->lock($id);
            if ($o['status'] !== 'AWAITING_PAYMENT') throw DomainError::invalid('order not awaiting payment');
            if ($db->value("SELECT 1 FROM payments WHERE provider = 'manual_transfer' AND provider_ref = ?", [$reference])) throw DomainError::invalid('reference already recorded');
            $pid = Db::uuid();
            $db->insert('payments', ['id' => $pid, 'order_id' => $o['id'], 'provider' => 'manual_transfer', 'provider_ref' => $reference,
                'amount_satang' => $amountSatang, 'recorded_by' => $a->id]);
            $db->insert('payment_events', ['id' => Db::uuid(), 'payment_id' => $pid, 'order_id' => $o['id'], 'event_type' => 'manual.recorded',
                'payload' => json_encode(['reference' => $reference]), 'verified' => 1]);
            $paid = (int) $db->value("SELECT COALESCE(SUM(CASE WHEN direction = 'charge' THEN amount_satang ELSE -amount_satang END), 0) FROM payments WHERE order_id = ?", [$o['id']]);
            Audit::log($db, $a->id, 'payment.recorded', 'payment', $pid, ['order_id' => $o['id'], 'amount' => $amountSatang]);
            if ($paid >= (int) $o['total_satang']) $this->move($a, $o, 'PAYMENT_CONFIRMED', 'manual transfer ' . $reference);
            return $pid;
        });
    }

    /** Refuses partners without a VERIFIED required skill or free capacity this week. */
    public function assignPartner(Actor $a, string $orderId, string $partnerId, string $skill, string $instructions = ''): string
    {
        Policy::requireAdmin($a);
        $partnerId = Validate::uuid($partnerId, 'partner');
        $skill = Validate::enum($skill, Validate::SKILLS, 'skill');
        $instructions = Validate::text($instructions, 'instructions', 8000);
        $week = date('Y-m-d', strtotime('monday this week'));
        return $this->db->tx(function (Db $db) use ($a, $orderId, $partnerId, $skill, $instructions, $week) {
            $o = $this->lock($orderId);
            if (!$db->value('SELECT 1 FROM production_partners WHERE id = ? AND is_verified = 1 AND is_active = 1', [$partnerId])) throw DomainError::invalid('partner not verified/active');
            if (!$db->value('SELECT 1 FROM partner_skills WHERE partner_id = ? AND skill = ? AND verified_at IS NOT NULL', [$partnerId, $skill])) throw DomainError::invalid("partner lacks verified skill {$skill}");
            if ($db->exec('UPDATE partner_capacity SET committed_jobs = committed_jobs + 1 WHERE partner_id = ? AND week_start = ? AND committed_jobs < max_jobs', [$partnerId, $week]) !== 1) {
                throw DomainError::invalid('partner has no capacity this week');
            }
            $jid = Db::uuid();
            $db->insert('production_jobs', ['id' => $jid, 'order_id' => $o['id'], 'partner_id' => $partnerId, 'required_skill' => $skill,
                'instructions' => $instructions, 'missing_materials' => '[]', 'week_start' => $week]);
            $db->exec('UPDATE orders SET assigned_partner_id = ? WHERE id = ?', [$partnerId, $o['id']]);
            Audit::log($db, $a->id, 'order.assign_partner', 'order', $o['id'], ['partner_id' => $partnerId, 'skill' => $skill]);
            return $jid;
        });
    }

    /** Live counts for the admin overview — always computed from current rows. */
    public function overview(Actor $a): array
    {
        Policy::requireAdmin($a);
        $c = fn(string $sql, array $p = []) => (int) $this->db->value($sql, $p);
        return [
            'newRequests' => $c("SELECT COUNT(*) FROM design_requests WHERE status = 'SUBMITTED'"),
            'inReview' => $c("SELECT COUNT(*) FROM design_requests WHERE status IN ('UNDER_REVIEW','NEEDS_INFORMATION','FEASIBILITY_REVIEW')"),
            'awaitingQuote' => $c("SELECT COUNT(*) FROM design_requests WHERE status = 'QUOTE_PREPARING'"),
            'quoteSent' => $c("SELECT COUNT(*) FROM design_requests WHERE status = 'QUOTE_SENT'"),
            'awaitingPayment' => $c("SELECT COUNT(*) FROM orders WHERE status = 'AWAITING_PAYMENT'"),
            'activeOrders' => $c('SELECT COUNT(*) FROM orders WHERE status IN (' . Db::in(self::ACTIVE) . ')', self::ACTIVE),
            'garments' => $c('SELECT COUNT(*) FROM garments'),
            'customers' => $c("SELECT COUNT(*) FROM user_roles WHERE role = 'customer'"),
            'listingsPending' => $c("SELECT (SELECT COUNT(*) FROM marketplace_listings WHERE status = 'pending_review') + (SELECT COUNT(*) FROM swap_listings WHERE status = 'pending_review')"),
            'slipsWaiting' => $c("SELECT COUNT(DISTINCT s.order_id) FROM payment_slips s JOIN orders o ON o.id = s.order_id WHERE o.status = 'AWAITING_PAYMENT'"),
            'paidTotal' => $c("SELECT COALESCE(SUM(CASE WHEN direction = 'charge' THEN amount_satang ELSE -amount_satang END), 0) FROM payments"),
            'popular' => $this->db->all("SELECT COALESCE(t.name_th, '') AS name_th, COALESCE(t.name_en, '') AS name_en, COUNT(*) AS n FROM design_requests r LEFT JOIN design_templates t ON t.id = r.template_id
                WHERE r.status <> 'DRAFT' GROUP BY r.template_id, t.name_th, t.name_en ORDER BY n DESC LIMIT 6"),
        ];
    }

    private function lock(string $id): array
    {
        $o = $this->db->one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [Validate::uuid($id)]);
        if (!$o) throw DomainError::notFound();
        return $o;
    }
    private function move(Actor $a, array $o, string $to, ?string $note): void
    {
        if (!$this->db->value('SELECT 1 FROM order_transitions WHERE from_status = ? AND to_status = ?', [$o['status'], $to])) {
            throw DomainError::invalid("illegal order transition {$o['status']} -> {$to}");
        }
        $this->db->exec('UPDATE orders SET status = ? WHERE id = ? AND status = ?', [$to, $o['id'], $o['status']]);
        $this->db->insert('order_status_history', ['id' => Db::uuid(), 'order_id' => $o['id'], 'from_status' => $o['status'],
            'to_status' => $to, 'actor_id' => $a->id, 'note' => $note]);
        Audit::log($this->db, $a->id, 'order.status', 'order', $o['id'], ['from' => $o['status'], 'to' => $to]);
    }
}
