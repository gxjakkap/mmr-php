<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Http;
use App\Services\{GarmentService, MarketService, NotificationService, StyleService, UserService};

final class StyleController extends Controller
{
    public function style(): string
    {
        $a = $this->actor(); $st = new StyleService($this->db);
        $garments = (new GarmentService($this->db))->listMine($a);
        return $this->view('style/index', ['garments' => $garments, 'ideas' => $st->suggestions($garments), 'outfits' => $st->outfits($a)]);
    }
    public function saveOutfit(): never
    {
        $ids = array_filter((array) ($_POST['garments'] ?? []), 'is_string');
        (new StyleService($this->db))->saveOutfit($this->actor(), $ids, (string) $this->input('name'));
        $this->ok(t('common.saved'), '/style');
    }
    public function deleteOutfit(string $id): never
    {
        (new StyleService($this->db))->deleteOutfit($this->actor(), $id);
        $this->ok(t('common.deleted'), '/style');
    }
    public function favorite(): never
    {
        (new StyleService($this->db))->toggleFavorite($this->actor(), (string) $this->input('target'));
        Http::redirect(Http::safePath($this->input('_back'), '/saved'));
    }
    public function saved(): string
    {
        $a = $this->actor();
        $favs = (new StyleService($this->db))->favorites($a);
        $tpls = array_column((new UserService($this->db))->templates(), null, 'slug');
        $m = new MarketService($this->db); $items = [];
        foreach ($favs as $f) if (str_starts_with($f, 'm:')) { try { $items[] = $m->get($a, substr($f, 2)); } catch (\App\Core\DomainError) {} }
        return $this->view('style/saved', ['favs' => $favs, 'templates' => array_values(array_filter(array_map(fn($f) => str_starts_with($f, 't:') ? ($tpls[substr($f, 2)] ?? null) : null, $favs))), 'items' => $items]);
    }
    public function inbox(): string
    {
        $a = $this->actor(); $n = new NotificationService($this->db);
        return $this->view('style/inbox', ['items' => $n->list($a), 'seen' => $n->seenAt($a)]);
    }
    public function markSeen(): never
    {
        (new NotificationService($this->db))->markSeen($this->actor());
        Http::redirect('/inbox');
    }
}
