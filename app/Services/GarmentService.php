<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy, Upload};

final class GarmentService
{
    public const MAX_IMAGES = 8;

    public function __construct(private Db $db) {}

    public function listMine(Actor $a, ?string $category = null): array
    {
        $sql = 'SELECT g.*, (SELECT gi.id FROM garment_images gi WHERE gi.garment_id = g.id ORDER BY gi.sort_order LIMIT 1) AS cover_image_id
                FROM garments g WHERE g.owner_id = ?';
        $p = [$a->id];
        if ($category !== null && in_array($category, Validate::CATEGORIES, true)) { $sql .= ' AND g.category = ?'; $p[] = $category; }
        return $this->db->all($sql . ' ORDER BY g.created_at DESC', $p);
    }

    /** Owner-only fetch (for edit/delete/assess). */
    public function getOwned(Actor $a, string $id): array
    {
        $g = $this->db->one('SELECT * FROM garments WHERE id = ? AND owner_id = ?', [Validate::uuid($id), $a->id]);
        if (!$g) throw DomainError::notFound();
        return $g;
    }

    public function create(Actor $a, array $input): string
    {
        $row = Validate::garment($input);
        $id = Db::uuid();
        $this->db->insert('garments', ['id' => $id, 'owner_id' => $a->id] + $row);
        Audit::log($this->db, $a->id, 'garment.created', 'garment', $id);
        return $id;
    }

    public function update(Actor $a, string $id, array $input): void
    {
        $this->getOwned($a, $id);
        $row = Validate::garment($input);
        $sets = implode(', ', array_map(static fn($k) => "`{$k}` = ?", array_keys($row)));
        $this->db->exec("UPDATE garments SET {$sets} WHERE id = ? AND owner_id = ?", [...array_values($row), $id, $a->id]);
    }

    public function delete(Actor $a, string $id): void
    {
        $this->getOwned($a, $id);
        if ($this->db->value('SELECT 1 FROM design_requests WHERE garment_id = ? LIMIT 1', [$id])) throw DomainError::invalid('delete_blocked');
        $paths = array_column($this->db->all('SELECT storage_path FROM garment_images WHERE garment_id = ?', [$id]), 'storage_path');
        $this->db->exec('DELETE FROM garments WHERE id = ? AND owner_id = ?', [$id, $a->id]);
        foreach ($paths as $p) Upload::delete($p);
        Audit::log($this->db, $a->id, 'garment.deleted', 'garment', $id);
    }

    /**
     * @param array<int, array{tmp_name:string, size:int, error:int}> $files
     * @return int number stored
     */
    public function addImages(Actor $a, string $garmentId, array $files): int
    {
        $this->getOwned($a, $garmentId);
        if (!$files) return 0;
        $existing = (int) $this->db->value('SELECT COUNT(*) FROM garment_images WHERE garment_id = ?', [$garmentId]);
        if ($existing + count($files) > self::MAX_IMAGES) throw DomainError::invalid('too_many_photos');
        $checked = array_map(static fn($f) => Upload::inspect($f['tmp_name'], (int) $f['size'], (int) $f['error']) + ['tmp' => $f['tmp_name']], $files);
        $n = 0;
        foreach ($checked as $i => $c) {
            $imgId = Db::uuid();
            $rel = "{$a->id}/{$garmentId}/{$imgId}.{$c['ext']}";
            Upload::store($c['tmp'], $rel);
            try {
                $this->db->insert('garment_images', ['id' => $imgId, 'garment_id' => $garmentId, 'owner_id' => $a->id,
                    'storage_path' => $rel, 'mime_type' => $c['mime'], 'size_bytes' => $c['size'], 'sort_order' => $existing + $i]);
            } catch (\Throwable $e) {
                Upload::delete($rel);
                throw $e;
            }
            $n++;
        }
        return $n;
    }

    public function deleteImage(Actor $a, string $imageId): string
    {
        $img = $this->db->one('SELECT id, garment_id, storage_path FROM garment_images WHERE id = ? AND owner_id = ?', [Validate::uuid($imageId), $a->id]);
        if (!$img) throw DomainError::notFound();
        $this->db->exec('DELETE FROM garment_images WHERE id = ?', [$img['id']]);
        Upload::delete($img['storage_path']);
        return $img['garment_id'];
    }

    public function images(string $garmentId): array
    {
        return $this->db->all('SELECT id, storage_path, mime_type, sort_order FROM garment_images WHERE garment_id = ? ORDER BY sort_order', [$garmentId]);
    }

    /** For MediaController: returns the image row only if the caller may view the garment. */
    public function imageForViewer(Actor $a, string $imageId): array
    {
        $img = $this->db->one('SELECT id, garment_id, storage_path, mime_type FROM garment_images WHERE id = ?', [Validate::uuid($imageId)]);
        if (!$img || !Policy::canViewGarment($this->db, $a, $img['garment_id'])) throw DomainError::notFound();
        return $img;
    }

    public function history(Actor $a, string $garmentId): array
    {
        $this->getOwned($a, $garmentId);
        return [
            'assessments' => $this->db->all('SELECT id, created_at, summary FROM garment_assessments WHERE garment_id = ? ORDER BY created_at DESC', [$garmentId]),
            'requests' => $this->db->all('SELECT id, title, status, created_at FROM design_requests WHERE garment_id = ? ORDER BY created_at DESC', [$garmentId]),
        ];
    }
}
