<?php
declare(strict_types=1);

use App\Core\I18n;
use App\Core\Csrf;

/** HTML-escape. Every dynamic value in a view goes through this. */
function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
/** Translate a dot key, e.g. t('wardrobe.title'). */
function t(string $key, array $replace = []): string
{
    return I18n::get($key, $replace);
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}
function url(string $path, array $query = []): string
{
    $q = array_filter($query, static fn($v) => $v !== null && $v !== '');
    return $path . ($q ? '?' . http_build_query($q) : '');
}
function money(int $satang): string
{
    return App\Domain\Money::format($satang, I18n::locale());
}
function fmt_date(?string $dt, bool $time = false): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    if (I18n::locale() === 'th') {
        $months = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        $s = (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . ((int) date('Y', $ts) + 543);
    } else {
        $s = date('j M Y', $ts);
    }
    return $time ? $s . ' ' . date('H:i', $ts) : $s;
}
function json_list(mixed $v): array
{
    if (is_array($v)) return $v;
    if (!is_string($v) || $v === '') return [];
    $d = json_decode($v, true);
    return is_array($d) ? $d : [];
}
