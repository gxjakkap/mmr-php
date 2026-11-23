<?php
declare(strict_types=1);
namespace App\Core;

/** Minimal .env loader. Real environment variables take precedence. */
final class Env
{
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            self::$vars[$k] = trim($v, "\"'");
        }
    }
    public static function get(string $key, ?string $default = null): ?string
    {
        $v = getenv($key);
        if ($v !== false) return $v;
        return self::$vars[$key] ?? $default;
    }
    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        return $v === null ? $default : in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }
    public static function set(string $key, string $value): void { self::$vars[$key] = $value; }
}
