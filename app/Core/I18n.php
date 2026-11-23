<?php
declare(strict_types=1);
namespace App\Core;

final class I18n
{
    private static string $locale = 'th';
    private static array $dict = [];

    public static function setLocale(string $l): void
    {
        self::$locale = $l === 'en' ? 'en' : 'th';
        self::$dict = require BASE_PATH . '/app/Lang/' . self::$locale . '.php';
    }
    public static function locale(): string { return self::$locale; }
    /** Dot-path lookup; for map sections (rec, reqStatus…) the remainder is used as a literal key. */
    public static function get(string $key, array $replace = []): string
    {
        if (!self::$dict) self::setLocale(self::$locale);
        $node = self::$dict;
        $parts = explode('.', $key);
        while ($parts) {
            $p = array_shift($parts);
            if (is_array($node) && array_key_exists($p, $node)) { $node = $node[$p]; continue; }
            $rest = implode('.', array_merge([$p], $parts));
            if (is_array($node) && array_key_exists($rest, $node)) { $node = $node[$rest]; break; }
            return $key;
        }
        if (!is_string($node)) return $key;
        foreach ($replace as $k => $v) $node = str_replace(':' . $k, (string) $v, $node);
        return $node;
    }
    public static function dict(): array { return self::$dict; }
}
