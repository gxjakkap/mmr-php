<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DomainError, Http, RateLimiter, Session, Upload};
use App\Services\{AssessmentService, GarmentService};

final class WardrobeController extends Controller
{
    private function svc(): GarmentService { return new GarmentService($this->db); }

    public function index(): string
    {
        $a = $this->actor();
        $cat = $this->query('category');
        $col = fn(string $sql) => array_flip(array_column($this->db->all($sql, array_fill(0, substr_count($sql, '?'), $a->id)), 'garment_id'));
        return $this->view('wardrobe/index', [
            'all' => $this->svc()->listMine($a), 'category' => $cat, 'q' => trim((string) $this->query('q')), 'filter' => $this->query('f') ?: 'all',
            'assessed' => $col('SELECT DISTINCT garment_id FROM garment_assessments WHERE owner_id = ?'),
            'inRequest' => $col("SELECT garment_id FROM design_requests WHERE customer_id = ? AND status NOT IN ('DRAFT','CANCELLED','QUOTE_REJECTED')"),
            'listed' => $col("SELECT garment_id FROM marketplace_listings WHERE seller_id = ? AND status IN ('pending_review','active','reserved')
                              UNION SELECT garment_id FROM swap_listings WHERE owner_id = ? AND status IN ('pending_review','active','matched')"),
        ]);
    }
    public function create(): string
    {
        return $this->view('wardrobe/form', ['g' => Session::takeOld() + ['category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'good'], 'id' => null]);
    }
    public function store(): never
    {
        $a = $this->actor();
        try {
            $id = $this->svc()->create($a, $_POST);
        } catch (DomainError $e) {
            $this->fail($e, '/wardrobe/new', $_POST);
        }
        $this->uploadInto($a, $id);
        Http::redirect("/wardrobe/{$id}");
    }
    public function show(string $id): string
    {
        $a = $this->actor();
        $g = $this->svc()->getOwned($a, $id);
        return $this->view('wardrobe/show', ['g' => $g, 'images' => $this->svc()->images($id), 'history' => $this->svc()->history($a, $id),
            'sel' => max(0, (int) $this->query('img')),
            'listings' => $this->db->all('SELECT id, status, price_satang, created_at FROM marketplace_listings WHERE garment_id = ? AND seller_id = ? ORDER BY created_at', [$id, $a->id]),
            'swaps' => $this->db->all('SELECT id, status, created_at FROM swap_listings WHERE garment_id = ? AND owner_id = ? ORDER BY created_at', [$id, $a->id])]);
    }
    public function edit(string $id): string
    {
        $g = $this->svc()->getOwned($this->actor(), $id);
        $g['composition_text'] = implode(', ', array_map(fn($c) => "{$c['pct']}% {$c['fiber']}", json_list($g['fabric_composition'])));
        $g['defects'] = json_list($g['defects']);
        $g['measurements'] = json_list($g['measurements']);
        return $this->view('wardrobe/form', ['g' => Session::takeOld() + $g, 'id' => $id, 'imageCount' => count($this->svc()->images($id))]);
    }
    public function update(string $id): never
    {
        $a = $this->actor();
        try { $this->svc()->update($a, $id, $_POST); }
        catch (DomainError $e) { $this->fail($e, "/wardrobe/{$id}/edit", $_POST); }
        $this->uploadInto($a, $id);
        $this->ok(t('common.saved'), "/wardrobe/{$id}");
    }
    public function destroy(string $id): never
    {
        if (empty($_POST['confirm'])) $this->fail(t('wardrobe.deleteConfirm'), "/wardrobe/{$id}");
        try { $this->svc()->delete($this->actor(), $id); }
        catch (DomainError $e) { $this->fail($e, "/wardrobe/{$id}"); }
        $this->ok(t('common.deleted'), '/wardrobe');
    }
    public function addImages(string $id): never
    {
        $this->uploadInto($this->actor(), $id);
        Http::redirect("/wardrobe/{$id}");
    }
    public function deleteImage(string $imageId): never
    {
        $gid = $this->svc()->deleteImage($this->actor(), $imageId);
        Http::redirect("/wardrobe/{$gid}");
    }

    private function uploadInto(\App\Core\Actor $a, string $id): void
    {
        $files = Upload::files('photos');
        if (!$files) return;
        if (!RateLimiter::hit('upload:' . $a->id, 60, 3600)) { Session::flash('error', t('auth.tooMany')); return; }
        try {
            $n = $this->svc()->addImages($a, $id, $files);
            Session::flash('success', t('wardrobe.uploadedN', ['n' => $n]));
        } catch (DomainError $e) {
            Session::flash('error', self::errorText($e)); // garment is saved; only the photos failed
        }
    }

    public function assessForm(string $id): string
    {
        return $this->view('wardrobe/assess', ['g' => $this->svc()->getOwned($this->actor(), $id)]);
    }
    public function assess(string $id): never
    {
        try { $aid = (new AssessmentService($this->db))->run($this->actor(), $id, $_POST); }
        catch (DomainError $e) { $this->fail($e, "/wardrobe/{$id}/assess"); }
        Http::redirect("/assessments/{$aid}");
    }
    public function assessment(string $id): string
    {
        return $this->view('wardrobe/assessment', ['a' => (new AssessmentService($this->db))->get($this->actor(), $id)]);
    }
}
