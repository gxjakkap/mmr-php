<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\DomainError;
use App\Services\UserService;

final class AccountController extends Controller
{
    public function show(): string
    {
        $a = $this->actor();
        $c = fn(string $sql) => (int) $this->db->value($sql, array_fill(0, substr_count($sql, '?'), $a->id));
        return $this->view('account', ['profile' => (new UserService($this->db))->profile($a), 'stats' => [
            $c('SELECT COUNT(*) FROM garments WHERE owner_id = ?'),
            $c("SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status = 'COMPLETED'"),
            $c("SELECT (SELECT COUNT(*) FROM marketplace_listings WHERE seller_id = ? AND status = 'sold') + (SELECT COUNT(*) FROM swap_requests r JOIN swap_listings l ON l.id = r.listing_id WHERE l.owner_id = ? AND r.status = 'completed')"),
        ]]);
    }
    public function update(): never
    {
        try { (new UserService($this->db))->updateProfile($this->actor(), $_POST); }
        catch (DomainError $e) { $this->fail($e, '/account'); }
        $this->ok(t('account.saved'), '/account');
    }
}
