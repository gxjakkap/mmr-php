<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, Http};
use App\Services\{MarketService, StyleService, UserService};

final class HomeController extends Controller
{
    public function home(): string
    {
        $a = Auth::actor();
        return $this->view('home', [
            'templates' => (new UserService($this->db))->templates(),
            'designers' => $this->db->all('SELECT public_name, bio, specialties FROM designers WHERE is_active = 1 ORDER BY created_at LIMIT 8'),
            'listings' => $a ? array_slice((new MarketService($this->db))->browse(null, null), 0, 4) : [],
            'favs' => $a ? (new StyleService($this->db))->favorites($a) : [],
            'firstGarment' => $a ? $this->db->value('SELECT id FROM garments WHERE owner_id = ? ORDER BY created_at DESC LIMIT 1', [$a->id]) : null,
        ]);
    }
    public function locale(): never
    {
        $l = $this->input('locale') === 'en' ? 'en' : 'th';
        setcookie('memorii_locale', $l, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        $this->back('/');
    }
    public function learn(): string { return $this->view('pages/learn'); }
    public function privacy(): string { return $this->view('pages/privacy'); }
    public function terms(): string { return $this->view('pages/terms'); }
    public function contact(): string { return $this->view('pages/contact'); }
}
