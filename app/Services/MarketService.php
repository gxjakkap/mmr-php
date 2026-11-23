<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy};

/**
 * Rewear marketplace. Listings need staff approval before anyone else can see them.
 * Buyers send purchase requests; the seller accepts one (listing becomes reserved) and marks it sold.
 * No payment or shipping is handled here: buyer and seller arrange that after acceptance.
 */
final class MarketService
{
    public const VISIBLE = ['active', 'reserved', 'sold'];

    public function __construct(private Db $db) {}

    private const SELECT = 'SELECT l.*, g.category, g.`condition`, g.name AS garment_name, u.display_name AS seller_name,
        (SELECT gi.id FROM garment_images gi WHERE gi.garment_id = l.garment_id ORDER BY gi.sort_order LIMIT 1) AS cover_image_id
        FROM marketplace_listings l JOIN garments g ON g.id = l.garment_id JOIN users u ON u.id = l.seller_id';

    public function browse(?string $q, ?string $category): array
    {
        $sql = self::SELECT . " WHERE l.status IN ('active','reserved')";
        $p = [];
        if ($q !== null && trim($q) !== '') { $sql .= ' AND l.title LIKE ?'; $p[] = '%' . mb_substr(trim($q), 0, 60) . '%'; }
        if ($category !== null && in_array($category, Validate::CATEGORIES, true)) { $sql .= ' AND g.category = ?'; $p[] = $category; }
        return $this->db->all($sql . ' ORDER BY l.created_at DESC LIMIT 200', $p);
    }
    public function mine(Actor $a): array
    {
        return $this->db->all(self::SELECT . " WHERE l.seller_id = ? AND l.status <> 'withdrawn' ORDER BY l.created_at DESC", [$a->id]);
    }
    public function pending(Actor $a): array
    {
        Policy::requireAdmin($a);
        return $this->db->all(self::SELECT . " WHERE l.status = 'pending_review' ORDER BY l.created_at");
    }

    /** Listing as the caller may see it: others only once approved. */
    public function get(Actor $a, string $id): array
    {
        $l = $this->db->one(self::SELECT . ' WHERE l.id = ?', [Validate::uuid($id)]);
        if (!$l) throw DomainError::notFound();
        if ($l['seller_id'] !== $a->id && !$a->isAdmin() && !in_array($l['status'], self::VISIBLE, true)) throw DomainError::notFound();
        return $l;
    }
    public function detail(Actor $a, string $id): array
    {
        $l = $this->get($a, $id);
        $isSeller = $l['seller_id'] === $a->id;
        return [
            'listing' => $l,
            'isSeller' => $isSeller,
            'images' => $this->db->all('SELECT id FROM garment_images WHERE garment_id = ? ORDER BY sort_order', [$l['garment_id']]),
            'offers' => $isSeller || $a->isAdmin()
                ? $this->db->all('SELECT o.*, u.display_name AS buyer_name FROM marketplace_orders o JOIN users u ON u.id = o.buyer_id WHERE o.listing_id = ? ORDER BY o.created_at', [$l['id']])
                : [],
            'myOffer' => $this->db->one("SELECT * FROM marketplace_orders WHERE listing_id = ? AND buyer_id = ? AND status <> 'cancelled' ORDER BY created_at DESC LIMIT 1", [$l['id'], $a->id]),
        ];
    }

    public function create(Actor $a, string $garmentId, array $in): string
    {
        $g = (new GarmentService($this->db))->getOwned($a, $garmentId);
        if (!$this->db->value('SELECT 1 FROM garment_images WHERE garment_id = ? LIMIT 1', [$g['id']])) throw DomainError::invalid('photo_required');
        self::assertFree($this->db, $g['id']);
        try { $price = \App\Domain\Money::thbToSatang((string) ($in['price'] ?? '')); } catch (\InvalidArgumentException) { throw DomainError::invalid('invalid_price'); }
        if ($price < 100 || $price > 100_000_000) throw DomainError::invalid('invalid_price');
        $id = Db::uuid();
        $this->db->insert('marketplace_listings', ['id' => $id, 'seller_id' => $a->id, 'garment_id' => $g['id'],
            'title' => Validate::text($in['title'] ?? $g['name'], 'title', 120, 1),
            'condition_disclosure' => Validate::text($in['disclosure'] ?? '', 'disclosure', 2000, 10),
            'price_satang' => $price, 'size_label' => Validate::optText($in['size'] ?? '', 'size', 20), 'status' => 'pending_review']);
        Audit::log($this->db, $a->id, 'listing.created', 'marketplace_listing', $id);
        return $id;
    }

    /** A garment may only be in one live listing, swap or open request at a time. */
    public static function assertFree(Db $db, string $garmentId): void
    {
        $busy = $db->value("SELECT 1 FROM marketplace_listings WHERE garment_id = ? AND status IN ('pending_review','active','reserved') UNION ALL
            SELECT 1 FROM swap_listings WHERE garment_id = ? AND status IN ('pending_review','active','matched') UNION ALL
            SELECT 1 FROM design_requests WHERE garment_id = ? AND status NOT IN ('CANCELLED','QUOTE_REJECTED','DRAFT') LIMIT 1", [$garmentId, $garmentId, $garmentId]);
        if ($busy) throw DomainError::invalid('garment_busy');
    }

    public function moderate(Actor $a, string $id, bool $approve, string $note = ''): void
    {
        Policy::requireAdmin($a);
        $n = $this->db->exec("UPDATE marketplace_listings SET status = ?, moderation_note = ?, moderated_by = ?, moderated_at = NOW() WHERE id = ? AND status = 'pending_review'",
            [$approve ? 'active' : 'rejected', Validate::optText($note, 'note', 1000), $a->id, Validate::uuid($id)]);
        if (!$n) throw DomainError::invalid('not_pending');
        Audit::log($this->db, $a->id, $approve ? 'listing.approved' : 'listing.rejected', 'marketplace_listing', $id);
    }

    public function requestToBuy(Actor $a, string $id, string $message): void
    {
        $this->db->tx(function (Db $db) use ($a, $id, $message) {
            $l = $db->one('SELECT * FROM marketplace_listings WHERE id = ? FOR UPDATE', [Validate::uuid($id)]);
            if (!$l || $l['status'] !== 'active') throw DomainError::invalid('not_available');
            if ($l['seller_id'] === $a->id) throw DomainError::forbidden('own listing');
            if ($db->value("SELECT 1 FROM marketplace_orders WHERE listing_id = ? AND buyer_id = ? AND status IN ('requested','accepted')", [$l['id'], $a->id])) throw DomainError::invalid('already_requested');
            $db->insert('marketplace_orders', ['id' => Db::uuid(), 'listing_id' => $l['id'], 'buyer_id' => $a->id, 'status' => 'requested',
                'price_satang' => (int) $l['price_satang'], 'message' => Validate::optText($message, 'message', 500)]);
        });
    }
    public function cancelRequest(Actor $a, string $offerId): string
    {
        $o = $this->db->one("SELECT * FROM marketplace_orders WHERE id = ? AND buyer_id = ? AND status = 'requested'", [Validate::uuid($offerId), $a->id]);
        if (!$o) throw DomainError::notFound();
        $this->db->exec("UPDATE marketplace_orders SET status = 'cancelled', decided_at = NOW() WHERE id = ?", [$o['id']]);
        return $o['listing_id'];
    }
    public function decide(Actor $a, string $offerId, bool $accept): string
    {
        return $this->db->tx(function (Db $db) use ($a, $offerId, $accept) {
            $o = $db->one('SELECT * FROM marketplace_orders WHERE id = ? FOR UPDATE', [Validate::uuid($offerId)]);
            if (!$o) throw DomainError::notFound();
            $l = $db->one('SELECT * FROM marketplace_listings WHERE id = ? FOR UPDATE', [$o['listing_id']]);
            if ($l['seller_id'] !== $a->id) throw DomainError::notFound();
            if ($o['status'] !== 'requested' || $l['status'] !== 'active') throw DomainError::invalid('not_available');
            $db->exec('UPDATE marketplace_orders SET status = ?, decided_at = NOW() WHERE id = ?', [$accept ? 'accepted' : 'declined', $o['id']]);
            if ($accept) {
                $db->exec("UPDATE marketplace_listings SET status = 'reserved' WHERE id = ?", [$l['id']]);
                $db->exec("UPDATE marketplace_orders SET status = 'declined', decided_at = NOW() WHERE listing_id = ? AND id <> ? AND status = 'requested'", [$l['id'], $o['id']]);
            }
            Audit::log($db, $a->id, $accept ? 'listing.offer_accepted' : 'listing.offer_declined', 'marketplace_listing', $l['id']);
            return $l['id'];
        });
    }
    /** Seller: reserved → sold, or withdraw a listing that is not sold. */
    public function setStatus(Actor $a, string $id, string $to): void
    {
        $allowed = ['sold' => ['reserved'], 'withdrawn' => ['pending_review', 'active', 'reserved']][$to] ?? null;
        if ($allowed === null) throw DomainError::invalid('bad status');
        $this->db->tx(function (Db $db) use ($a, $id, $to, $allowed) {
            $l = $db->one('SELECT * FROM marketplace_listings WHERE id = ? AND seller_id = ? FOR UPDATE', [Validate::uuid($id), $a->id]);
            if (!$l) throw DomainError::notFound();
            if (!in_array($l['status'], $allowed, true)) throw DomainError::invalid('not_allowed');
            $db->exec('UPDATE marketplace_listings SET status = ? WHERE id = ?', [$to, $l['id']]);
            if ($to === 'sold') $db->exec("UPDATE marketplace_orders SET status = 'completed' WHERE listing_id = ? AND status = 'accepted'", [$l['id']]);
            if ($to === 'withdrawn') $db->exec("UPDATE marketplace_orders SET status = 'cancelled', decided_at = NOW() WHERE listing_id = ? AND status IN ('requested','accepted')", [$l['id']]);
        });
    }
}
