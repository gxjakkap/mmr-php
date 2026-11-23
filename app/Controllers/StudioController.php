<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, DomainError, Http, Session};
use App\Services\{GarmentService, RequestService, StyleService, UserService};

/** Template browsing is public; choosing a garment and sending a request needs an account. */
final class StudioController extends Controller
{
    public function index(): string
    {
        $a = Auth::actor();
        $mode = in_array($this->query('mode'), ['template', 'ai', 'custom'], true) ? $this->query('mode') : 'template';
        $kind = in_array($this->query('kind'), ['wear', 'bag', 'acc', 'home'], true) ? $this->query('kind') : null;
        $garments = $a ? (new GarmentService($this->db))->listMine($a) : [];
        $templates = (new UserService($this->db))->templates();
        $find = fn(array $list, ?string $id) => array_values(array_filter($list, fn($x) => $x['id'] === $id))[0] ?? null;
        $template = $find($templates, $this->query('template'));
        if ($template && !$kind) $kind = null;
        return $this->view('studio/index', [
            'mode' => $mode, 'kind' => $kind, 'fit' => $this->query('fit') === '1', 'garments' => $garments, 'templates' => $templates,
            'garment' => $find($garments, $this->query('garment')), 'template' => $template,
            'favs' => $a ? (new StyleService($this->db))->favorites($a) : [], 'old' => Session::takeOld(),
        ]);
    }
    public function store(): never
    {
        $back = url('/studio', ['garment' => $this->input('garment_id'), 'template' => $this->input('template_id'), 'mode' => $this->input('mode')]);
        try { $id = (new RequestService($this->db))->create($this->actor(), $_POST); }
        catch (DomainError $e) { $this->fail($e, $back, $_POST); }
        Http::redirect("/requests/{$id}");
    }
}
