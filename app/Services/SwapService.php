<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy};

/**
 * Swap: owner lists a garment (staff approve it), members propose one of their own garments,
 * owner accepts one proposal (listing → matched), proposer confirms (mutually_accepted),
 * then either side marks the swap completed. Each side pays to ship its own item.
 */
final class SwapService
{
    public function __construct(private Db $db) {}

    private const SELECT = 'SELECT l.*, g.name AS title, g.category, g.`condition`, u.display_name AS owner_name,
        (SELECT gi.id FROM garment_images gi WHERE gi.garment_id = l.garment_id ORDER BY gi.sort_order LIMIT 1) AS cover_image_id
        FROM swap_listings l JOIN garments g ON g.id = l.garment_id JOIN users u ON u.id = l.owner_id';

    public function browse(): array
    {
        return $this->db->all(self::SELECT . " WHERE l.status = 'active' ORDER BY l.created_at DESC LIMIT 200");
    }
    public function mine(Actor $a): array
    {
        return $this->db->all(self::SELECT . " WHERE l.owner_id = ? AND l.status <> 'withdrawn' ORDER BY l.created_at DESC", [$a->id]);
    }
    public function pending(Actor $a): array
    {
        Policy::requireAdmin($a);
        return $this->db->all(self::SELECT . " WHERE l.status = 'pending_review' ORDER BY l.created_at");
    }
    public function get(Actor $a, string $id): array
    {
        $l = $this->db->one(self::SELECT . ' WHERE l.id = ?', [Validate::uuid($id)]);
        if (!$l) throw DomainError::notFound();
        $involved = $l['owner_id'] === $a->id || $a->isAdmin()
            || $this->db->value('SELECT 1 FROM swap_requests WHERE listing_id = ? AND requester_id = ?', [$l['id'], $a->id]);
        if (!$involved && $l['status'] !== 'active') throw DomainError::notFound();
        return $l;
    }
    public function detail(Actor $a, string $id): array
    {
        $l = $this->get($a, $id);
        $isOwner = $l['owner_id'] === $a->id;
        $q = 'SELECT r.*, g.name AS offered_name, u.display_name AS requester_name,
              (SELECT gi.id FROM garment_images gi WHERE gi.garment_id = r.offered_garment_id ORDER BY gi.sort_order LIMIT 1) AS offered_image_id
              FROM swap_requests r JOIN garments g ON g.id = r.offered_garment_id JOIN users u ON u.id = r.requester_id WHERE r.listing_id = ?';
        return [
            'listing' => $l, 'isOwner' => $isOwner,
            'images' => $this->db->all('SELECT id FROM garment_images WHERE garment_id = ? ORDER BY sort_order', [$l['garment_id']]),
            'proposals' => $isOwner || $a->isAdmin() ? $this->db->all($q . ' ORDER BY r.created_at', [$l['id']]) : [],
            'mine' => $this->db->one($q . " AND r.requester_id = ? AND r.status <> 'cancelled' ORDER BY r.created_at DESC LIMIT 1", [$l['id'], $a->id]),
        ];
    }

    public function create(Actor $a, string $garmentId, array $in): string
    {
        $g = (new GarmentService($this->db))->getOwned($a, $garmentId);
        if (!$this->db->value('SELECT 1 FROM garment_images WHERE garment_id = ? LIMIT 1', [$g['id']])) throw DomainError::invalid('photo_required');
        MarketService::assertFree($this->db, $g['id']);
        $id = Db::uuid();
        $this->db->insert('swap_listings', ['id' => $id, 'owner_id' => $a->id, 'garment_id' => $g['id'],
            'condition_disclosure' => Validate::text($in['disclosure'] ?? '', 'disclosure', 2000, 10),
            'looking_for' => Validate::optText($in['looking_for'] ?? '', 'looking_for', 200), 'status' => 'pending_review']);
        Audit::log($this->db, $a->id, 'swap.created', 'swap_listing', $id);
        return $id;
    }
    public function moderate(Actor $a, string $id, bool $approve): void
    {
        Policy::requireAdmin($a);
        $n = $this->db->exec("UPDATE swap_listings SET status = ?, moderated_by = ?, moderated_at = NOW() WHERE id = ? AND status = 'pending_review'",
            [$approve ? 'active' : 'rejected', $a->id, Validate::uuid($id)]);
        if (!$n) throw DomainError::invalid('not_pending');
        Audit::log($this->db, $a->id, $approve ? 'swap.approved' : 'swap.rejected', 'swap_listing', $id);
    }
    public function propose(Actor $a, string $id, string $garmentId, string $message): void
    {
        $this->db->tx(function (Db $db) use ($a, $id, $garmentId, $message) {
            $l = $db->one('SELECT * FROM swap_listings WHERE id = ? FOR UPDATE', [Validate::uuid($id)]);
            if (!$l || $l['status'] !== 'active') throw DomainError::invalid('not_available');
            if ($l['owner_id'] === $a->id) throw DomainError::forbidden('own listing');
            $g = (new GarmentService($db))->getOwned($a, $garmentId);
            if (!$db->value('SELECT 1 FROM garment_images WHERE garment_id = ? LIMIT 1', [$g['id']])) throw DomainError::invalid('photo_required');
            if ($db->value("SELECT 1 FROM swap_requests WHERE listing_id = ? AND requester_id = ? AND status IN ('proposed','accepted_by_owner','mutually_accepted')", [$l['id'], $a->id])) throw DomainError::invalid('already_requested');
            $db->insert('swap_requests', ['id' => Db::uuid(), 'listing_id' => $l['id'], 'requester_id' => $a->id, 'offered_garment_id' => $g['id'],
                'status' => 'proposed', 'message' => Validate::optText($message, 'message', 500)]);
        });
    }
    /** Owner accepts one proposal; others are declined. */
    public function accept(Actor $a, string $requestId): string
    {
        return $this->db->tx(function (Db $db) use ($a, $requestId) {
            $r = $db->one('SELECT * FROM swap_requests WHERE id = ? FOR UPDATE', [Validate::uuid($requestId)]);
            if (!$r) throw DomainError::notFound();
            $l = $db->one('SELECT * FROM swap_listings WHERE id = ? FOR UPDATE', [$r['listing_id']]);
            if ($l['owner_id'] !== $a->id) throw DomainError::notFound();
            if ($l['status'] !== 'active' || $r['status'] !== 'proposed') throw DomainError::invalid('not_available');
            $db->exec("UPDATE swap_requests SET status = 'accepted_by_owner' WHERE id = ?", [$r['id']]);
            $db->exec("UPDATE swap_requests SET status = 'declined' WHERE listing_id = ? AND id <> ? AND status = 'proposed'", [$l['id'], $r['id']]);
            $db->exec("UPDATE swap_listings SET status = 'matched' WHERE id = ?", [$l['id']]);
            return $l['id'];
        });
    }
    public function confirm(Actor $a, string $requestId): string
    {
        $r = $this->db->one("SELECT * FROM swap_requests WHERE id = ? AND requester_id = ? AND status = 'accepted_by_owner'", [Validate::uuid($requestId), $a->id]);
        if (!$r) throw DomainError::notFound();
        $this->db->exec("UPDATE swap_requests SET status = 'mutually_accepted' WHERE id = ?", [$r['id']]);
        return $r['listing_id'];
    }
    public function complete(Actor $a, string $requestId): string
    {
        $r = $this->db->one("SELECT r.*, l.owner_id FROM swap_requests r JOIN swap_listings l ON l.id = r.listing_id WHERE r.id = ? AND r.status = 'mutually_accepted'", [Validate::uuid($requestId)]);
        if (!$r || ($r['owner_id'] !== $a->id && $r['requester_id'] !== $a->id)) throw DomainError::notFound();
        $this->db->exec("UPDATE swap_requests SET status = 'completed' WHERE id = ?", [$r['id']]);
        return $r['listing_id'];
    }
    public function cancel(Actor $a, string $requestId): string
    {
        $r = $this->db->one("SELECT * FROM swap_requests WHERE id = ? AND requester_id = ? AND status = 'proposed'", [Validate::uuid($requestId), $a->id]);
        if (!$r) throw DomainError::notFound();
        $this->db->exec("UPDATE swap_requests SET status = 'cancelled' WHERE id = ?", [$r['id']]);
        return $r['listing_id'];
    }
    public function withdraw(Actor $a, string $id): void
    {
        $n = $this->db->exec("UPDATE swap_listings SET status = 'withdrawn' WHERE id = ? AND owner_id = ? AND status IN ('pending_review','active')", [Validate::uuid($id), $a->id]);
        if (!$n) throw DomainError::invalid('not_allowed');
        $this->db->exec("UPDATE swap_requests SET status = 'declined' WHERE listing_id = ? AND status = 'proposed'", [$id]);
    }
}
