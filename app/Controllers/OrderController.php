<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DomainError, Http, RateLimiter, Upload};
use App\Services\{OrderService, PaymentService};

final class OrderController extends Controller
{
    public function show(string $id): string
    {
        $a = $this->actor();
        $d = (new OrderService($this->db))->detail($a, $id);
        if ($d['order']['customer_id'] !== $a->id && $a->isAdmin()) Http::redirect("/admin/orders/{$id}");
        $p = new PaymentService($this->db);
        return $this->view('orders/show', ['d' => $d, 'pay' => $p->settings(), 'slips' => $p->slips($id)]);
    }
    public function transition(string $id): never
    {
        (new OrderService($this->db))->transition($this->actor(), $id, (string) $this->input('to'), (string) $this->input('note'));
        $this->ok(t('admin.moved'), Http::safePath($this->input('_back'), "/orders/{$id}"));
    }
    public function slip(string $id): never
    {
        $a = $this->actor();
        $f = Upload::files('slip')[0] ?? null;
        if (!$f) $this->fail(L('กรุณาเลือกไฟล์สลิป', 'Please choose a slip image'), "/orders/{$id}");
        if (!RateLimiter::hit('upload:' . $a->id, 60, 3600)) $this->fail(t('auth.tooMany'), "/orders/{$id}");
        try { (new PaymentService($this->db))->addSlip($a, $id, $f); }
        catch (DomainError $e) { $this->fail($e, "/orders/{$id}"); }
        $this->ok(L('แนบสลิปแล้ว ทีมงานจะตรวจยอดและยืนยัน', 'Slip attached — our team will check and confirm'), "/orders/{$id}");
    }
}
