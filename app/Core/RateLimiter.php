<?php
declare(strict_types=1);
namespace App\Core;

/** Fixed-window limiter stored in MySQL (works across PHP-FPM workers). */
final class RateLimiter
{
    public static function hit(string $bucket, int $max, int $windowSeconds): bool
    {
        $db = Db::get();
        $key = substr(hash('sha256', $bucket), 0, 64);
        return $db->tx(function (Db $db) use ($key, $max, $windowSeconds) {
            $row = $db->one('SELECT hits, window_start FROM rate_limits WHERE bucket = ? FOR UPDATE', [$key]);
            $now = time();
            if (!$row || strtotime($row['window_start']) + $windowSeconds <= $now) {
                $db->exec('REPLACE INTO rate_limits (bucket, hits, window_start) VALUES (?, 1, ?)', [$key, date('Y-m-d H:i:s', $now)]);
                return true;
            }
            if ((int) $row['hits'] >= $max) return false;
            $db->exec('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?', [$key]);
            return true;
        });
    }
    public static function clientIp(): string { return (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'); }
}
