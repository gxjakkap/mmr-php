<?php
declare(strict_types=1);
namespace App\Core;

/**
 * Record-level authorization — the PHP equivalent of the Supabase RLS policies.
 * Services call these before every read or write; controllers never query directly.
 */
final class Policy
{
    public static function designerIdOf(Db $db, Actor $a): ?string
    {
        if (!$a->has('designer')) return null;
        return $db->value('SELECT id FROM designers WHERE user_id = ? AND is_active = 1', [$a->id]);
    }
    public static function partnerIdOf(Db $db, Actor $a): ?string
    {
        if (!$a->has('tailor')) return null;
        return $db->value('SELECT id FROM production_partners WHERE user_id = ? AND is_active = 1', [$a->id]);
    }

    /** Which workflow actor labels the caller holds for this request. */
    public static function requestActors(Db $db, Actor $a, array $request): array
    {
        $out = [];
        if ($request['customer_id'] === $a->id) $out[] = 'customer';
        if ($a->isAdmin()) $out[] = 'admin';
        $did = self::designerIdOf($db, $a);
        if ($did !== null && $request['assigned_designer_id'] === $did) $out[] = 'designer';
        return $out;
    }
    public static function canReadRequest(Db $db, Actor $a, array $request): bool
    {
        return self::requestActors($db, $a, $request) !== [] || $a->has('support');
    }

    public static function canViewGarment(Db $db, Actor $a, string $garmentId): bool
    {
        if ($a->isAdmin() || $a->has('support')) return true;
        if ($db->value('SELECT 1 FROM garments WHERE id = ? AND owner_id = ?', [$garmentId, $a->id])) return true;
        $did = self::designerIdOf($db, $a);
        if ($did && $db->value('SELECT 1 FROM design_requests WHERE garment_id = ? AND assigned_designer_id = ?', [$garmentId, $did])) return true;
        $pid = self::partnerIdOf($db, $a);
        if ($pid && $db->value('SELECT 1 FROM design_requests r JOIN orders o ON o.request_id = r.id WHERE r.garment_id = ? AND o.assigned_partner_id = ?', [$garmentId, $pid])) return true;
        // Rewear / Swap: photos of approved public listings are visible to signed-in members
        if ($db->value("SELECT 1 FROM marketplace_listings WHERE garment_id = ? AND status IN ('active','reserved','sold') LIMIT 1", [$garmentId])) return true;
        if ($db->value("SELECT 1 FROM swap_listings WHERE garment_id = ? AND status = 'active' LIMIT 1", [$garmentId])) return true;
        // a garment offered in a swap is visible to the listing owner; a matched listing stays visible to its counterpart
        if ($db->value('SELECT 1 FROM swap_requests r JOIN swap_listings l ON l.id = r.listing_id WHERE r.offered_garment_id = ? AND l.owner_id = ? LIMIT 1', [$garmentId, $a->id])) return true;
        if ($db->value('SELECT 1 FROM swap_listings l JOIN swap_requests r ON r.listing_id = l.id WHERE l.garment_id = ? AND r.requester_id = ? LIMIT 1', [$garmentId, $a->id])) return true;
        return false;
    }

    public static function orderActors(Db $db, Actor $a, array $order): array
    {
        $out = [];
        if ($order['customer_id'] === $a->id) $out[] = 'customer';
        if ($a->isAdmin()) $out[] = 'admin';
        if ($a->has('qc')) $out[] = 'qc';
        $pid = self::partnerIdOf($db, $a);
        if ($pid !== null && $order['assigned_partner_id'] === $pid) $out[] = 'tailor';
        return $out;
    }
    public static function canReadOrder(Db $db, Actor $a, array $order): bool
    {
        return self::orderActors($db, $a, $order) !== [] || $a->has('support');
    }

    public static function requireAdmin(Actor $a): void
    {
        if (!$a->isAdmin()) throw DomainError::forbidden('admin only');
    }
}
