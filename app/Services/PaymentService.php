<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy, Upload};

/** Bank-transfer details (admin-edited) and customer transfer slips (evidence only; staff still record the payment). */
final class PaymentService
{
    public const FIELDS = ['bank' => 60, 'account_no' => 30, 'account_name' => 80, 'promptpay' => 20, 'note' => 200];
    public const MAX_SLIPS = 10;

    public function __construct(private Db $db) {}

    public function settings(): array
    {
        $v = $this->db->value("SELECT v FROM app_settings WHERE k = 'payment'");
        $d = is_string($v) ? (json_decode($v, true) ?: []) : [];
        return array_merge(array_fill_keys(array_keys(self::FIELDS), ''), array_intersect_key($d, self::FIELDS));
    }
    public function saveSettings(Actor $a, array $in): void
    {
        Policy::requireAdmin($a);
        $v = [];
        foreach (self::FIELDS as $k => $max) $v[$k] = Validate::text($in[$k] ?? '', $k, $max);
        $this->db->exec("INSERT INTO app_settings (k, v, updated_by) VALUES ('payment', ?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_by = VALUES(updated_by)", [$v, $a->id]);
        Audit::log($this->db, $a->id, 'settings.payment', 'app_settings', null);
    }

    public function slips(string $orderId): array
    {
        return $this->db->all('SELECT id, created_at FROM payment_slips WHERE order_id = ? ORDER BY created_at', [$orderId]);
    }
    /** Customer attaches a slip while the order awaits payment. */
    public function addSlip(Actor $a, string $orderId, array $file): void
    {
        $o = $this->db->one('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [Validate::uuid($orderId), $a->id]);
        if (!$o) throw DomainError::notFound();
        if ($o['status'] !== 'AWAITING_PAYMENT') throw DomainError::invalid('not_awaiting_payment');
        if ((int) $this->db->value('SELECT COUNT(*) FROM payment_slips WHERE order_id = ?', [$o['id']]) >= self::MAX_SLIPS) throw DomainError::invalid('too_many_photos');
        $c = Upload::inspect($file['tmp_name'], (int) $file['size'], (int) $file['error']);
        $id = Db::uuid();
        $rel = "{$a->id}/{$o['id']}/{$id}.{$c['ext']}";
        Upload::store($file['tmp_name'], $rel);
        try {
            $this->db->insert('payment_slips', ['id' => $id, 'order_id' => $o['id'], 'uploaded_by' => $a->id, 'storage_path' => $rel, 'mime_type' => $c['mime'], 'size_bytes' => $c['size']]);
        } catch (\Throwable $e) { Upload::delete($rel); throw $e; }
        Audit::log($this->db, $a->id, 'order.slip_uploaded', 'order', $o['id']);
    }
    public function slipForViewer(Actor $a, string $slipId): array
    {
        $s = $this->db->one('SELECT s.*, o.customer_id FROM payment_slips s JOIN orders o ON o.id = s.order_id WHERE s.id = ?', [Validate::uuid($slipId)]);
        if (!$s || ($s['customer_id'] !== $a->id && !$a->isAdmin())) throw DomainError::notFound();
        return $s;
    }
}
