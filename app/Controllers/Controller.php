<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Actor, Auth, Db, DomainError, Http, Session, View};

abstract class Controller
{
    protected Db $db;
    public function __construct() { $this->db = Db::get(); }

    protected function actor(): Actor
    {
        $a = Auth::actor();
        if (!$a) Http::redirect('/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        return $a;
    }
    protected function view(string $tpl, array $data = []): string
    {
        return View::render($tpl, $data + ['actor' => Auth::actor()]);
    }
    protected function back(string $fallback): never
    {
        Http::redirect(Http::safePath($_POST['_back'] ?? null, $fallback));
    }
    protected function ok(string $message, string $to): never
    {
        Session::flash('success', $message);
        Http::redirect($to);
    }
    protected function fail(DomainError|string $e, string $to, array $old = []): never
    {
        Session::flash('error', self::errorText($e));
        if ($old) Session::flashOld($old);
        Http::redirect($to);
    }
    /** User-facing text for an error: translated if the message is a known code. */
    public static function errorText(DomainError|string $e): string
    {
        $m = is_string($e) ? $e : $e->getMessage();
        $tr = t('err.' . $m);
        return $tr !== 'err.' . $m ? $tr : $m;
    }
    protected function input(string $key, mixed $default = ''): mixed { return $_POST[$key] ?? $default; }
    protected function query(string $key): ?string { $v = $_GET[$key] ?? null; return is_string($v) ? $v : null; }
}
