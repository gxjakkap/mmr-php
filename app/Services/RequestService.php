<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy};
use App\Domain\Money;

/**
 * Design request lifecycle, proposals, quotations and request conversations.
 * Every status change runs in a transaction with SELECT … FOR UPDATE and is checked against request_transitions.
 */
final class RequestService
{
    public function __construct(private Db $db) {}

    // ------------------------------------------------------------------ reads
    public function listForCustomer(Actor $a): array
    {
        return $this->db->all('SELECT r.id, r.title, r.status, r.created_at, o.id AS order_id, o.status AS order_status
            FROM design_requests r LEFT JOIN orders o ON o.request_id = r.id WHERE r.customer_id = ? ORDER BY r.created_at DESC', [$a->id]);
    }
    public function listForAdmin(Actor $a, ?string $status): array
    {
        Policy::requireAdmin($a);
        $sql = "SELECT r.id, r.title, r.status, r.submitted_at, u.display_name AS customer_name, d.public_name AS designer_name
                FROM design_requests r JOIN users u ON u.id = r.customer_id LEFT JOIN designers d ON d.id = r.assigned_designer_id
                WHERE r.status <> 'DRAFT'";
        $p = [];
        if ($status !== null && in_array($status, \App\Domain\Workflow::REQUEST_STATUSES, true)) { $sql .= ' AND r.status = ?'; $p[] = $status; }
        return $this->db->all($sql . ' ORDER BY r.submitted_at IS NULL, r.submitted_at ASC LIMIT 200', $p);
    }
    public function listForDesigner(Actor $a): array
    {
        $did = Policy::designerIdOf($this->db, $a);
        if (!$did) throw DomainError::forbidden('designer only');
        return $this->db->all('SELECT id, title, status, updated_at FROM design_requests WHERE assigned_designer_id = ? ORDER BY updated_at DESC', [$did]);
    }

    public function loadForActor(Actor $a, string $id): array
    {
        $r = $this->db->one('SELECT * FROM design_requests WHERE id = ?', [Validate::uuid($id)]);
        if (!$r || !Policy::canReadRequest($this->db, $a, $r)) throw DomainError::notFound(); // 404, not 403: don't leak existence
        return $r;
    }

    /** Everything a request page needs, filtered for the caller. */
    public function detail(Actor $a, string $id): array
    {
        $r = $this->loadForActor($a, $id);
        $actors = Policy::requestActors($this->db, $a, $r);
        $isStaff = $a->isStaff();
        $garment = $this->db->one('SELECT id, name, category, `condition`, defects, fabric_primary, measurements FROM garments WHERE id = ?', [$r['garment_id']]);
        $quotes = $this->db->all('SELECT * FROM quotations WHERE request_id = ? ORDER BY revision_no DESC', [$r['id']]);
        foreach ($quotes as &$q) $q['items'] = $this->db->all('SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order', [$q['id']]);
        unset($q);
        $conv = $this->db->value('SELECT id FROM conversations WHERE design_request_id = ?', [$r['id']]);
        $messages = $conv ? $this->db->all('SELECT m.id, m.sender_id, m.body, m.is_internal, m.created_at, u.display_name
            FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.conversation_id = ? ' . ($isStaff ? '' : 'AND m.is_internal = 0 ') . 'ORDER BY m.created_at', [$conv]) : [];
        return [
            'request' => $r,
            'actors' => $actors,
            'garment' => $garment,
            'images' => (new GarmentService($this->db))->images($r['garment_id']),
            'template' => $r['template_id'] ? $this->db->one('SELECT * FROM design_templates WHERE id = ?', [$r['template_id']]) : null,
            // Only the public designer name — never staff contact details.
            'designer' => $r['assigned_designer_id'] ? $this->db->one('SELECT id, public_name FROM designers WHERE id = ?', [$r['assigned_designer_id']]) : null,
            'history' => $this->db->all('SELECT to_status, note, created_at FROM design_request_status_history WHERE request_id = ? ORDER BY created_at', [$r['id']]),
            'versions' => $this->db->all('SELECT * FROM design_versions WHERE request_id = ? ORDER BY version_no DESC', [$r['id']]),
            'quotations' => $quotes,
            'conversation_id' => $conv,
            'messages' => $messages,
            'order' => $this->db->one('SELECT id, order_no, status FROM orders WHERE request_id = ?', [$r['id']]),
        ];
    }

    // ----------------------------------------------------------------- writes
    public function create(Actor $a, array $in): string
    {
        $garmentId = Validate::uuid($in['garment_id'] ?? '', 'garment');
        (new GarmentService($this->db))->getOwned($a, $garmentId); // must be the caller's garment
        $mode = Validate::enum($in['mode'] ?? '', ['template', 'custom'], 'mode');
        $templateId = null;
        if ($mode === 'template') {
            $templateId = Validate::uuid($in['template_id'] ?? '', 'template');
            if (!$this->db->value('SELECT 1 FROM design_templates WHERE id = ? AND is_active = 1', [$templateId])) throw DomainError::invalid('invalid template');
        }
        if (empty($in['acknowledge'])) throw DomainError::invalid('acknowledge_required');
        $title = Validate::text($in['title'] ?? '', 'title', 120, 1);
        $req = Validate::text($in['requirements'] ?? '', 'requirements', 4000, $mode === 'custom' ? 1 : 0);
        $budget = Validate::optInt($in['budget_max_thb'] ?? null, 'budget', 0, 1_000_000);
        $id = Db::uuid();
        $this->db->insert('design_requests', ['id' => $id, 'customer_id' => $a->id, 'garment_id' => $garmentId, 'template_id' => $templateId,
            'mode' => $mode, 'title' => $title, 'requirements' => $req, 'budget_max_thb' => $budget]);
        Audit::log($this->db, $a->id, 'request.created', 'design_request', $id);
        if (!empty($in['submit'])) $this->transition($a, $id, 'SUBMITTED');
        return $id;
    }

    /** Generic, non-system transition. */
    public function transition(Actor $a, string $id, string $to, ?string $note = null): void
    {
        $note = Validate::optText($note ?? '', 'note', 2000);
        $this->db->tx(function (Db $db) use ($a, $id, $to, $note) {
            $r = $this->lock($id);
            if (!Policy::canReadRequest($db, $a, $r)) throw DomainError::notFound();
            $actors = Policy::requestActors($db, $a, $r);
            if (!$actors || !$db->value('SELECT 1 FROM request_transitions WHERE from_status = ? AND to_status = ? AND actor IN (' . Db::in($actors) . ')',
                [$r['status'], $to, ...$actors])) {
                throw DomainError::forbidden("not allowed: {$r['status']} -> {$to}");
            }
            if ($to === 'SUBMITTED') {
                $db->exec('UPDATE design_requests SET requires_physical_inspection = 1, submitted_at = NOW() WHERE id = ?', [$r['id']]);
                if (!$db->value('SELECT 1 FROM conversations WHERE design_request_id = ?', [$r['id']])) {
                    $db->insert('conversations', ['id' => Db::uuid(), 'design_request_id' => $r['id']]);
                }
            }
            $this->move($a, $r, $to, $note);
        });
    }

    public function assignDesigner(Actor $a, string $id, string $designerId): void
    {
        Policy::requireAdmin($a);
        $designerId = Validate::uuid($designerId, 'designer');
        $this->db->tx(function (Db $db) use ($a, $id, $designerId) {
            if (!$db->value('SELECT 1 FROM designers WHERE id = ? AND is_active = 1', [$designerId])) throw DomainError::invalid('designer not active');
            $r = $this->lock($id);
            if (!in_array($r['status'], ['UNDER_REVIEW', 'FEASIBILITY_REVIEW', 'QUOTE_PREPARING', 'QUOTE_REJECTED'], true)) {
                throw DomainError::invalid("cannot assign designer in status {$r['status']}");
            }
            $db->exec('UPDATE design_requests SET assigned_designer_id = ? WHERE id = ?', [$designerId, $r['id']]);
            Audit::log($db, $a->id, 'request.assign_designer', 'design_request', $r['id'], ['designer_id' => $designerId]);
            if ($r['status'] !== 'QUOTE_PREPARING') $this->move($a, $r, 'QUOTE_PREPARING', null);
        });
    }

    /**
     * Assigned designer sends a proposal + quotation. Totals are computed here from the items.
     * @param array{description:string,difficulty:string,materials:string,feasibility_notes:string,valid_days:int|string,notes?:string,
     *              items: array<int, array{kind:string,description:string,quantity:string|float,unit_price_thb:string|float}>} $in
     */
    public function submitProposal(Actor $a, string $id, array $in): string
    {
        $desc = Validate::text($in['description'] ?? '', 'description', 6000, 10);
        $difficulty = Validate::enum($in['difficulty'] ?? '', ['low', 'medium', 'high'], 'difficulty');
        $materials = array_values(array_filter(array_map('trim', explode(',', Validate::text($in['materials'] ?? '', 'materials', 1000)))));
        $feas = Validate::text($in['feasibility_notes'] ?? '', 'feasibility_notes', 4000);
        $validDays = Validate::optInt($in['valid_days'] ?? 14, 'valid_days', 1, 60) ?? 14;
        $notes = Validate::optText($in['notes'] ?? '', 'notes', 4000);
        $items = [];
        foreach ((array) ($in['items'] ?? []) as $it) {
            $d = trim((string) ($it['description'] ?? ''));
            if ($d === '' && trim((string) ($it['unit_price_thb'] ?? '')) === '') continue; // blank row
            $qty = $it['quantity'] ?? '1';
            if (!is_numeric($qty) || (float) $qty <= 0 || (float) $qty > 1000) throw DomainError::invalid('invalid quantity');
            try { $price = Money::thbToSatang((string) ($it['unit_price_thb'] ?? '')); } catch (\InvalidArgumentException) { throw DomainError::invalid('invalid price'); }
            $items[] = ['kind' => Validate::enum($it['kind'] ?? '', Money::KINDS, 'kind'), 'description' => Validate::text($d, 'item description', 300, 1),
                'quantity' => round((float) $qty, 2), 'unit_price_satang' => $price];
        }
        if (count($items) < 1 || count($items) > 30) throw DomainError::invalid('quotation needs 1-30 items');
        $tot = Money::totals($items);
        if (!$tot['valid']) throw DomainError::invalid('discount exceeds subtotal');

        return $this->db->tx(function (Db $db) use ($a, $id, $desc, $difficulty, $materials, $feas, $validDays, $notes, $items, $tot) {
            $r = $this->lock($id);
            if (!in_array('designer', Policy::requestActors($db, $a, $r), true)) throw DomainError::forbidden('only the assigned designer may submit a proposal');
            if ($r['status'] !== 'QUOTE_PREPARING') throw DomainError::invalid("request is not awaiting a quotation (status {$r['status']})");

            $db->exec("UPDATE design_versions SET status = 'superseded' WHERE request_id = ? AND status = 'proposed'", [$r['id']]);
            $vno = (int) $db->value('SELECT COALESCE(MAX(version_no), 0) + 1 FROM design_versions WHERE request_id = ?', [$r['id']]);
            $vid = Db::uuid();
            $db->insert('design_versions', ['id' => $vid, 'request_id' => $r['id'], 'version_no' => $vno, 'author_id' => $a->id,
                'description' => $desc, 'difficulty' => $difficulty, 'materials' => json_encode($materials, JSON_UNESCAPED_UNICODE), 'feasibility_notes' => $feas]);

            $prev = $db->one('SELECT id, revision_no FROM quotations WHERE request_id = ? ORDER BY revision_no DESC LIMIT 1', [$r['id']]);
            $db->exec("UPDATE quotations SET status = 'superseded' WHERE request_id = ? AND status = 'sent'", [$r['id']]);
            $qid = Db::uuid();
            $db->insert('quotations', ['id' => $qid, 'request_id' => $r['id'], 'design_version_id' => $vid,
                'revision_no' => ($prev['revision_no'] ?? 0) + 1, 'supersedes_id' => $prev['id'] ?? null, 'prepared_by' => $a->id,
                'subtotal_satang' => $tot['subtotal'], 'discount_satang' => $tot['discount'], 'tax_rate_bp' => Money::VAT_BP,
                'tax_satang' => $tot['tax'], 'total_satang' => $tot['total'], 'notes' => $notes,
                'expires_at' => date('Y-m-d H:i:s', time() + $validDays * 86400)]);
            foreach ($items as $i => $it) {
                $db->insert('quotation_items', ['id' => Db::uuid(), 'quotation_id' => $qid, 'kind' => $it['kind'], 'description' => $it['description'],
                    'quantity' => $it['quantity'], 'unit_price_satang' => $it['unit_price_satang'], 'line_total_satang' => $tot['lines'][$i], 'sort_order' => $i]);
            }
            $this->move($a, $r, 'QUOTE_SENT', 'quotation r' . (($prev['revision_no'] ?? 0) + 1));
            Audit::log($db, $a->id, 'quotation.sent', 'quotation', $qid, ['request_id' => $r['id'], 'total' => $tot['total']]);
            return $qid;
        });
    }

    /** Customer accepts (creates the order) or rejects. Never alters the quotation's amounts. */
    public function respondQuotation(Actor $a, string $quotationId, bool $accept, ?string $reason = null): ?string
    {
        $reason = Validate::optText($reason ?? '', 'reason', 2000);
        $quotationId = Validate::uuid($quotationId, 'quotation');
        return $this->db->tx(function (Db $db) use ($a, $quotationId, $accept, $reason) {
            // Lock order: request first, then quotation (same as submitProposal) to avoid deadlocks.
            $reqId = $db->value('SELECT request_id FROM quotations WHERE id = ?', [$quotationId]);
            if (!$reqId) throw DomainError::notFound('quotation not found');
            $r = $this->lock($reqId);
            $q = $db->one('SELECT * FROM quotations WHERE id = ? FOR UPDATE', [$quotationId]);
            if ($r['customer_id'] !== $a->id) throw DomainError::notFound('quotation not found');
            if ($q['status'] !== 'sent') throw DomainError::invalid("quotation is {$q['status']}");
            if (strtotime($q['expires_at']) < time()) throw DomainError::invalid('quotation expired');

            $db->exec('UPDATE quotations SET status = ?, responded_at = NOW(), response_reason = ? WHERE id = ?', [$accept ? 'accepted' : 'rejected', $reason, $q['id']]);
            if ($q['design_version_id']) $db->exec('UPDATE design_versions SET status = ? WHERE id = ?', [$accept ? 'approved' : 'rejected', $q['design_version_id']]);
            if (!$accept) {
                $this->move($a, $r, 'QUOTE_REJECTED', $reason);
                return null;
            }
            $this->move($a, $r, 'QUOTE_ACCEPTED', null);
            $oid = Db::uuid();
            $db->insert('orders', ['id' => $oid, 'order_no' => 'MO-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'customer_id' => $r['customer_id'], 'request_id' => $r['id'], 'quotation_id' => $q['id'], 'total_satang' => (int) $q['total_satang']]);
            $db->exec('INSERT INTO order_items (id, order_id, kind, description, quantity, unit_price_satang, line_total_satang)
                       SELECT UUID(), ?, kind, description, quantity, unit_price_satang, line_total_satang FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order',
                [$oid, $q['id']]);
            $db->insert('order_status_history', ['id' => Db::uuid(), 'order_id' => $oid, 'from_status' => null, 'to_status' => 'AWAITING_PAYMENT',
                'actor_id' => $a->id, 'note' => 'quotation accepted']);
            Audit::log($db, $a->id, 'order.created', 'order', $oid, ['quotation_id' => $q['id']]);
            return $oid;
        });
    }

    public function sendMessage(Actor $a, string $requestId, string $body, bool $internal = false): void
    {
        $r = $this->loadForActor($a, $requestId);
        $body = Validate::text($body, 'message', 4000, 1);
        if ($internal && !$a->isStaff()) throw DomainError::forbidden('customers cannot post internal notes');
        $conv = $this->db->value('SELECT id FROM conversations WHERE design_request_id = ?', [$r['id']]);
        if (!$conv) throw DomainError::invalid('conversation not available');
        $this->db->insert('messages', ['id' => Db::uuid(), 'conversation_id' => $conv, 'sender_id' => $a->id, 'body' => $body, 'is_internal' => $internal ? 1 : 0]);
    }

    // --------------------------------------------------------------- internals
    private function lock(string $id): array
    {
        $r = $this->db->one('SELECT * FROM design_requests WHERE id = ? FOR UPDATE', [Validate::uuid($id)]);
        if (!$r) throw DomainError::notFound();
        return $r;
    }
    /** Applies a move that exists in request_transitions (any actor). Callers check WHO may do it. */
    private function move(Actor $a, array $r, string $to, ?string $note): void
    {
        if (!$this->db->value('SELECT 1 FROM request_transitions WHERE from_status = ? AND to_status = ?', [$r['status'], $to])) {
            throw DomainError::invalid("illegal request transition {$r['status']} -> {$to}");
        }
        $this->db->exec('UPDATE design_requests SET status = ? WHERE id = ? AND status = ?', [$to, $r['id'], $r['status']]);
        $this->db->insert('design_request_status_history', ['id' => Db::uuid(), 'request_id' => $r['id'], 'from_status' => $r['status'],
            'to_status' => $to, 'actor_id' => $a->id, 'note' => $note]);
        Audit::log($this->db, $a->id, 'request.status', 'design_request', $r['id'], ['from' => $r['status'], 'to' => $to]);
    }
}
