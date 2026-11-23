<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DomainError, Http, Session};
use App\Services\{GarmentService, MarketService, StyleService, SwapService};

/** Rewear marketplace and Swap. Members only (listing photos are private uploads). */
final class MarketController extends Controller
{
    private function m(): MarketService { return new MarketService($this->db); }
    private function s(): SwapService { return new SwapService($this->db); }

    public function index(): string
    {
        $a = $this->actor();
        $q = trim((string) $this->query('q')); $cat = $this->query('category');
        return $this->view('market/index', ['rows' => $this->m()->browse($q, $cat), 'mine' => $this->m()->mine($a), 'q' => $q, 'category' => $cat,
            'favs' => (new StyleService($this->db))->favorites($a)]);
    }
    public function sellForm(string $garmentId): string
    {
        $a = $this->actor(); $gs = new GarmentService($this->db);
        return $this->view('market/sell', ['g' => $gs->getOwned($a, $garmentId), 'images' => $gs->images($garmentId), 'old' => Session::takeOld()]);
    }
    public function sell(string $garmentId): never
    {
        try { $id = $this->m()->create($this->actor(), $garmentId, $_POST); }
        catch (DomainError $e) { $this->fail($e, "/wardrobe/{$garmentId}/sell", $_POST); }
        $this->ok(L('ส่งตรวจแล้ว ประกาศจะแสดงเมื่อทีมงานอนุมัติ', 'Submitted — it goes live after our team reviews it'), "/market/{$id}");
    }
    public function show(string $id): string
    {
        $a = $this->actor();
        return $this->view('market/show', ['d' => $this->m()->detail($a, $id), 'favs' => (new StyleService($this->db))->favorites($a)]);
    }
    public function buy(string $id): never
    {
        $this->m()->requestToBuy($this->actor(), $id, (string) $this->input('message'));
        $this->ok(L('ส่งคำขอซื้อแล้ว', 'Purchase request sent'), "/market/{$id}");
    }
    public function decide(string $offerId): never
    {
        $lid = $this->m()->decide($this->actor(), $offerId, $this->input('decision') === 'accept');
        $this->ok(t('common.saved'), "/market/{$lid}");
    }
    public function cancelOffer(string $offerId): never
    {
        $lid = $this->m()->cancelRequest($this->actor(), $offerId);
        $this->ok(t('common.saved'), "/market/{$lid}");
    }
    public function status(string $id): never
    {
        $this->m()->setStatus($this->actor(), $id, (string) $this->input('to'));
        $this->ok(t('common.saved'), "/market/{$id}");
    }

    public function swapIndex(): string
    {
        $a = $this->actor();
        return $this->view('swap/index', ['rows' => $this->s()->browse(), 'mine' => $this->s()->mine($a)]);
    }
    public function swapForm(string $garmentId): string
    {
        $a = $this->actor(); $gs = new GarmentService($this->db);
        return $this->view('swap/new', ['g' => $gs->getOwned($a, $garmentId), 'images' => $gs->images($garmentId), 'old' => Session::takeOld()]);
    }
    public function swapCreate(string $garmentId): never
    {
        try { $id = $this->s()->create($this->actor(), $garmentId, $_POST); }
        catch (DomainError $e) { $this->fail($e, "/wardrobe/{$garmentId}/swap", $_POST); }
        $this->ok(L('ส่งตรวจแล้ว', 'Submitted for review'), "/swap/{$id}");
    }
    public function swapShow(string $id): string
    {
        $a = $this->actor();
        $mine = array_values(array_filter((new GarmentService($this->db))->listMine($a), fn($g) => $g['cover_image_id']));
        return $this->view('swap/show', ['d' => $this->s()->detail($a, $id), 'myGarments' => $mine]);
    }
    public function swapPropose(string $id): never
    {
        $this->s()->propose($this->actor(), $id, (string) $this->input('garment_id'), (string) $this->input('message'));
        $this->ok(L('ส่งข้อเสนอแลกแล้ว', 'Swap offer sent'), "/swap/{$id}");
    }
    public function swapAct(string $requestId): never
    {
        $a = $this->actor();
        $lid = match ($this->input('do')) {
            'accept' => $this->s()->accept($a, $requestId), 'confirm' => $this->s()->confirm($a, $requestId),
            'complete' => $this->s()->complete($a, $requestId), 'cancel' => $this->s()->cancel($a, $requestId),
            default => throw DomainError::invalid('bad action'),
        };
        $this->ok(t('common.saved'), "/swap/{$lid}");
    }
    public function swapWithdraw(string $id): never
    {
        $this->s()->withdraw($this->actor(), $id);
        $this->ok(t('common.saved'), '/swap');
    }
}
