<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\DomainError;
use App\Domain\Money;
use App\Services\{MarketService, OrderService, PaymentService, RequestService, SwapService, UserService, Validate};
use App\Core\{Audit, Http, Policy};

/** Route access is already limited to the admin role; every service call re-checks it. */
final class AdminController extends Controller
{
    public function overview(): string
    {
        return $this->view('admin/overview', ['m' => (new OrderService($this->db))->overview($this->actor())]);
    }
    public function requests(): string
    {
        $s = $this->query('status');
        return $this->view('admin/requests', ['rows' => (new RequestService($this->db))->listForAdmin($this->actor(), $s), 'status' => $s]);
    }
    public function request(string $id): string
    {
        return $this->view('admin/request', ['d' => (new RequestService($this->db))->detail($this->actor(), $id),
            'designers' => (new UserService($this->db))->activeDesigners()]);
    }
    public function assign(string $id): never
    {
        (new RequestService($this->db))->assignDesigner($this->actor(), $id, (string) $this->input('designer_id'));
        $this->ok(t('admin.assignedOk'), "/admin/requests/{$id}");
    }
    public function orders(): string
    {
        $s = $this->query('status');
        return $this->view('admin/orders', ['rows' => (new OrderService($this->db))->listForAdmin($this->actor(), $s), 'status' => $s]);
    }
    public function order(string $id): string
    {
        return $this->view('admin/order', ['d' => (new OrderService($this->db))->detail($this->actor(), $id)]);
    }
    public function payment(string $id): never
    {
        try { $amount = Money::thbToSatang((string) $this->input('amount')); }
        catch (\InvalidArgumentException) { throw DomainError::invalid('invalid amount'); }
        (new OrderService($this->db))->recordManualPayment($this->actor(), $id, $amount, (string) $this->input('reference'));
        $this->ok(t('admin.paymentRecorded'), "/admin/orders/{$id}");
    }
    public function users(): string
    {
        return $this->view('admin/users', ['users' => (new UserService($this->db))->listForAdmin($this->actor())]);
    }
    public function grant(): never
    {
        (new UserService($this->db))->grantRole($this->actor(), trim((string) $this->input('user_id')), (string) $this->input('role'), (string) $this->input('public_name'));
        $this->ok(t('admin.roleGranted'), '/admin/users');
    }
    public function audit(): string
    {
        return $this->view('admin/audit', ['rows' => (new UserService($this->db))->audit($this->actor())]);
    }

    public function moderation(): string
    {
        $a = $this->actor();
        return $this->view('admin/moderation', ['market' => (new MarketService($this->db))->pending($a), 'swaps' => (new SwapService($this->db))->pending($a)]);
    }
    public function moderate(): never
    {
        $a = $this->actor(); $ok = $this->input('decision') === 'approve'; $id = (string) $this->input('id');
        if ($this->input('kind') === 'swap') (new SwapService($this->db))->moderate($a, $id, $ok);
        else (new MarketService($this->db))->moderate($a, $id, $ok, (string) $this->input('note'));
        $this->ok($ok ? L('อนุมัติแล้ว', 'Approved') : L('ไม่อนุมัติแล้ว', 'Rejected'), Http::safePath($this->input('_back'), '/admin/moderation'));
    }
    public function settings(): string
    {
        $a = $this->actor(); Policy::requireAdmin($a);
        return $this->view('admin/settings', ['pay' => (new PaymentService($this->db))->settings(),
            'designers' => $this->db->all('SELECT id, public_name, bio, specialties, is_active FROM designers ORDER BY public_name')]);
    }
    public function saveSettings(): never
    {
        (new PaymentService($this->db))->saveSettings($this->actor(), $_POST);
        $this->ok(t('common.saved'), '/admin/settings');
    }
    public function designerProfile(string $id): never
    {
        $a = $this->actor(); Policy::requireAdmin($a);
        $skills = array_values(array_intersect((array) ($_POST['specialties'] ?? []), Validate::SKILLS));
        $n = $this->db->exec('UPDATE designers SET public_name = ?, bio = ?, specialties = ? WHERE id = ?', [
            Validate::text($this->input('public_name'), 'public_name', 80, 1), Validate::optText($this->input('bio'), 'bio', 400), $skills, Validate::uuid($id)]);
        Audit::log($this->db, $a->id, 'designer.profile', 'designer', $id);
        $this->ok(t('common.saved'), '/admin/settings');
    }
}
