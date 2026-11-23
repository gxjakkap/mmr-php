<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DomainError, Session};
use App\Services\RequestService;

final class DesignerController extends Controller
{
    public function index(): string
    {
        return $this->view('designer/index', ['rows' => (new RequestService($this->db))->listForDesigner($this->actor())]);
    }
    public function show(string $id): string
    {
        $d = (new RequestService($this->db))->detail($this->actor(), $id);
        if (!in_array('designer', $d['actors'], true)) throw DomainError::notFound();
        return $this->view('designer/show', ['d' => $d, 'old' => Session::takeOld()]);
    }
    public function proposal(string $id): never
    {
        try { (new RequestService($this->db))->submitProposal($this->actor(), $id, $_POST); }
        catch (DomainError $e) { $this->fail($e, "/designer/requests/{$id}", $_POST); }
        $this->ok(t('reqStatus.QUOTE_SENT'), "/designer/requests/{$id}");
    }
}
