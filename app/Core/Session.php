<?php
declare(strict_types=1);
namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || Env::bool('SESSION_SECURE', false);
        session_name('memorii_sid');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
        // Idle timeout (default 8h)
        $idle = (int) Env::get('SESSION_IDLE_SECONDS', '28800');
        if (isset($_SESSION['_last']) && time() - $_SESSION['_last'] > $idle) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last'] = time();
    }
    public static function get(string $k, mixed $d = null): mixed { return $_SESSION[$k] ?? $d; }
    public static function set(string $k, mixed $v): void { $_SESSION[$k] = $v; }
    public static function forget(string $k): void { unset($_SESSION[$k]); }
    public static function regenerate(): void { session_regenerate_id(true); }
    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
            session_destroy();
        }
    }
    public static function flash(string $type, string $message): void { $_SESSION['_flash'][] = [$type, $message]; }
    /** @return array<int, array{0:string,1:string}> */
    public static function takeFlash(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }
    /** Old form input for re-rendering after a validation error. */
    public static function flashOld(array $input): void { $_SESSION['_old'] = $input; }
    public static function takeOld(): array { $o = $_SESSION['_old'] ?? []; unset($_SESSION['_old']); return $o; }
}
