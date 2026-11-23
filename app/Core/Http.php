<?php
declare(strict_types=1);
namespace App\Core;

final class Http
{
    public static function redirect(string $path, int $code = 303): never
    {
        // Only same-site relative paths (prevents open redirects).
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, "\n") || str_contains($path, "\r")) $path = '/';
        header('Location: ' . $path, true, $code);
        exit;
    }
    public static function safePath(mixed $v, string $fallback): string
    {
        return is_string($v) && str_starts_with($v, '/') && !str_starts_with($v, '//') && !preg_match('/[\r\n\\\\]/', $v) ? $v : $fallback;
    }
    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
