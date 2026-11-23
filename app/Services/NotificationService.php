<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Db};

/**
 * Notifications are derived from the append-only history tables (nothing is invented or duplicated).
 * Each item: at, icon, href, text-key + params. Views translate them.
 */
final class NotificationService
{
    public function __construct(private Db $db) {}

    public function list(Actor $a, int $limit = 60): array
    {
        $me = $a->id;
        $q = fn(string $sql, array $p) => $this->db->all($sql . ' ORDER BY at DESC LIMIT 60', $p);
        $items = [];
        foreach ($q("SELECT h.created_at AS at, h.to_status, r.id, r.title FROM design_request_status_history h JOIN design_requests r ON r.id = h.request_id
                     WHERE r.customer_id = ? AND (h.actor_id IS NULL OR h.actor_id <> ?)", [$me, $me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'chat', 'href' => '/requests/' . $r['id'], 'kind' => 'req', 'title' => $r['title'], 'status' => $r['to_status']];
        foreach ($q("SELECT m.created_at AS at, m.body, r.id, r.title FROM messages m JOIN conversations c ON c.id = m.conversation_id JOIN design_requests r ON r.id = c.design_request_id
                     WHERE r.customer_id = ? AND m.sender_id <> ? AND m.is_internal = 0", [$me, $me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'chat', 'href' => '/requests/' . $r['id'], 'kind' => 'msg', 'title' => $r['title'], 'body' => mb_substr($r['body'], 0, 80)];
        foreach ($q("SELECT h.created_at AS at, h.to_status, o.id, o.order_no FROM order_status_history h JOIN orders o ON o.id = h.order_id
                     WHERE o.customer_id = ? AND (h.actor_id IS NULL OR h.actor_id <> ?)", [$me, $me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'truck', 'href' => '/orders/' . $r['id'], 'kind' => 'order', 'title' => $r['order_no'], 'status' => $r['to_status']];
        foreach ($q("SELECT o.created_at AS at, l.id, l.title FROM marketplace_orders o JOIN marketplace_listings l ON l.id = o.listing_id WHERE l.seller_id = ?", [$me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'tag', 'href' => '/market/' . $r['id'], 'kind' => 'buy_request', 'title' => $r['title']];
        foreach ($q("SELECT o.decided_at AS at, o.status, l.id, l.title FROM marketplace_orders o JOIN marketplace_listings l ON l.id = o.listing_id
                     WHERE o.buyer_id = ? AND o.decided_at IS NOT NULL AND o.status IN ('accepted','declined','completed')", [$me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'tag', 'href' => '/market/' . $r['id'], 'kind' => $r['status'] === 'declined' ? 'buy_declined' : 'buy_accepted', 'title' => $r['title']];
        foreach ($q("SELECT moderated_at AS at, status, id, title FROM marketplace_listings WHERE seller_id = ? AND moderated_at IS NOT NULL", [$me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'shield', 'href' => '/market/' . $r['id'], 'kind' => $r['status'] === 'rejected' ? 'listing_rejected' : 'listing_active', 'title' => $r['title']];
        foreach ($q("SELECT r.created_at AS at, l.id, g.name AS title FROM swap_requests r JOIN swap_listings l ON l.id = r.listing_id JOIN garments g ON g.id = l.garment_id WHERE l.owner_id = ?", [$me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'swap', 'href' => '/swap/' . $r['id'], 'kind' => 'swap_offer', 'title' => $r['title']];
        foreach ($q("SELECT r.updated_at AS at, r.status, l.id, g.name AS title FROM swap_requests r JOIN swap_listings l ON l.id = r.listing_id JOIN garments g ON g.id = l.garment_id
                     WHERE r.requester_id = ? AND r.status IN ('accepted_by_owner','declined','completed')", [$me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'swap', 'href' => '/swap/' . $r['id'], 'kind' => 'swap_' . $r['status'], 'title' => $r['title']];
        foreach ($q("SELECT moderated_at AS at, l.status, l.id, g.name AS title FROM swap_listings l JOIN garments g ON g.id = l.garment_id WHERE l.owner_id = ? AND l.moderated_at IS NOT NULL", [$me]) as $r)
            $items[] = ['at' => $r['at'], 'icon' => 'shield', 'href' => '/swap/' . $r['id'], 'kind' => $r['status'] === 'rejected' ? 'listing_rejected' : 'listing_active', 'title' => $r['title']];
        usort($items, fn($x, $y) => strcmp((string) $y['at'], (string) $x['at']));
        return array_slice($items, 0, $limit);
    }
    public function seenAt(Actor $a): ?string
    {
        return $this->db->value('SELECT notifications_seen_at FROM users WHERE id = ?', [$a->id]);
    }
    public function unseen(Actor $a): int
    {
        $seen = $this->seenAt($a);
        return count(array_filter($this->list($a, 20), fn($n) => !$seen || $n['at'] > $seen));
    }
    public function markSeen(Actor $a): void
    {
        $this->db->exec('UPDATE users SET notifications_seen_at = NOW(6) WHERE id = ?', [$a->id]);
    }
}
