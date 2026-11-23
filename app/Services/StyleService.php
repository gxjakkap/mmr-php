<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Db, DomainError};

/** Favourites, saved outfits and rule-based outfit ideas (not an AI stylist). */
final class StyleService
{
    private const TOPS = ['denim_jacket', 'jacket', 'denim_shirt', 'shirt', 'tshirt'];
    private const BOTTOMS = ['jeans', 'trousers', 'skirt', 'denim_skirt'];

    public function __construct(private Db $db) {}

    public function favorites(Actor $a): array
    {
        return array_column($this->db->all('SELECT target FROM favorites WHERE user_id = ? ORDER BY created_at DESC', [$a->id]), 'target');
    }
    public function toggleFavorite(Actor $a, string $target): bool
    {
        if (!preg_match('/^(t:[a-z0-9\-]{1,60}|m:[0-9a-f\-]{36})$/', $target)) throw DomainError::invalid('bad target');
        if ($this->db->exec('DELETE FROM favorites WHERE user_id = ? AND target = ?', [$a->id, $target])) return false;
        if ((int) $this->db->value('SELECT COUNT(*) FROM favorites WHERE user_id = ?', [$a->id]) >= 300) throw DomainError::invalid('too_many');
        $this->db->insert('favorites', ['user_id' => $a->id, 'target' => $target]);
        return true;
    }

    /** @param array<int,array> $garments the caller's own garments (from GarmentService::listMine) */
    public function suggestions(array $garments): array
    {
        $gs = array_values(array_filter($garments, fn($g) => $g['condition'] !== 'damaged'));
        $by = fn(array $cats) => array_values(array_filter($gs, fn($g) => in_array($g['category'], $cats, true)));
        $tops = $by(self::TOPS); $bots = $by(self::BOTTOMS); $dresses = $by(['dress']); $bags = $by(['bag']);
        $out = [];
        foreach ($tops as $t) foreach ($bots as $b) {
            if (count($out) >= 6) break 2;
            $denim = ($t['fabric_primary'] === 'denim') + ($b['fabric_primary'] === 'denim');
            $out[] = ['items' => array_merge([$t, $b], $bags ? [$bags[0]] : []), 'why' => $denim === 2 ? 'double_denim' : 'top_bottom'];
        }
        foreach ($dresses as $d) {
            if (count($out) >= 8) break;
            $j = array_values(array_filter($tops, fn($x) => in_array($x['category'], ['denim_jacket', 'jacket'], true)))[0] ?? null;
            $out[] = ['items' => $j ? [$d, $j] : [$d], 'why' => $j ? 'dress_layer' : 'one_piece'];
        }
        return $out;
    }

    public function outfits(Actor $a): array
    {
        $rows = $this->db->all('SELECT * FROM outfits WHERE owner_id = ? ORDER BY created_at DESC', [$a->id]);
        foreach ($rows as &$o) {
            $o['items'] = $this->db->all('SELECT g.id, g.name, g.category, (SELECT gi.id FROM garment_images gi WHERE gi.garment_id = g.id ORDER BY gi.sort_order LIMIT 1) AS cover_image_id
                FROM outfit_items oi JOIN garments g ON g.id = oi.garment_id WHERE oi.outfit_id = ? ORDER BY oi.sort_order', [$o['id']]);
        }
        return $rows;
    }
    /** @param string[] $garmentIds */
    public function saveOutfit(Actor $a, array $garmentIds, string $name): string
    {
        $ids = array_values(array_unique(array_map(fn($x) => Validate::uuid($x, 'garment'), $garmentIds)));
        if (count($ids) < 2 || count($ids) > 8) throw DomainError::invalid('outfit_size');
        $owned = (int) $this->db->value('SELECT COUNT(*) FROM garments WHERE owner_id = ? AND id IN (' . Db::in($ids) . ')', [$a->id, ...$ids]);
        if ($owned !== count($ids)) throw DomainError::notFound();
        if ((int) $this->db->value('SELECT COUNT(*) FROM outfits WHERE owner_id = ?', [$a->id]) >= 100) throw DomainError::invalid('too_many');
        return $this->db->tx(function (Db $db) use ($a, $ids, $name) {
            $id = Db::uuid();
            $db->insert('outfits', ['id' => $id, 'owner_id' => $a->id, 'name' => Validate::text($name, 'name', 60)]);
            foreach ($ids as $i => $g) $db->insert('outfit_items', ['outfit_id' => $id, 'garment_id' => $g, 'sort_order' => $i]);
            return $id;
        });
    }
    public function deleteOutfit(Actor $a, string $id): void
    {
        $this->db->exec('DELETE FROM outfits WHERE id = ? AND owner_id = ?', [Validate::uuid($id), $a->id]);
    }
}
