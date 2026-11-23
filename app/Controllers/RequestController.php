<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DomainError, Http};
use App\Services\RequestService;

final class RequestController extends Controller
{
    private function svc(): RequestService { return new RequestService($this->db); }

    public function index(): string
    {
        return $this->view('requests/index', ['rows' => $this->svc()->listForCustomer($this->actor())]);
    }
    public function show(string $id): string
    {
        $a = $this->actor();
        $d = $this->svc()->detail($a, $id);
        if ($d['request']['customer_id'] !== $a->id) {
            // Staff use their own portals for the same record.
            if ($a->isAdmin()) Http::redirect("/admin/requests/{$id}");
            if (in_array('designer', $d['actors'], true)) Http::redirect("/designer/requests/{$id}");
        }
        return $this->view('requests/show', ['d' => $d]);
    }
    /** Shared by customer, admin and designer pages — the service decides who may do what. */
    public function transition(string $id): never
    {
        $this->svc()->transition($this->actor(), $id, (string) $this->input('to'), (string) $this->input('note'));
        $this->ok(t('admin.moved'), Http::safePath($this->input('_back'), "/requests/{$id}"));
    }
    public function message(string $id): never
    {
        $this->svc()->sendMessage($this->actor(), $id, (string) $this->input('body'), !empty($_POST['internal']));
        $this->back("/requests/{$id}");
    }
    public function respond(string $quotationId): never
    {
        $accept = $this->input('decision') === 'accept';
        $orderId = $this->svc()->respondQuotation($this->actor(), $quotationId, $accept, (string) $this->input('reason'));
        $orderId ? Http::redirect("/orders/{$orderId}") : $this->back('/requests');
    }
}
