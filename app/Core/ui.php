<?php
declare(strict_types=1);
// View helpers for the v3 photo-led UI. All output is escaped; icons are static SVG.

use App\Core\I18n;

/** Inline bilingual text for UI copy that has no dictionary key. */
function L(string $th, string $en): string
{
    return I18n::locale() === 'th' ? $th : $en;
}

const MEMORII_ICONS = [
    'home' => '<path d="M4 11l8-7 8 7v9H4z"/><path d="M10 20v-5h4v5"/>',
    'studio' => '<path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6z"/><path d="M18.5 15l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7z"/>',
    'hanger' => '<path d="M12 7a2 2 0 1 1 2-2c0 1.2-2 1.6-2 3v1"/><path d="M12 9l-9 6.5c-.8.6-.4 1.5.5 1.5h17c.9 0 1.3-.9.5-1.5z"/>',
    'box' => '<path d="M3 8l9-5 9 5v9l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v9"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
    'bell' => '<path d="M6 16V11a6 6 0 1 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
    'leaf' => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19l8-8"/>',
    'swap' => '<path d="M4 8h14l-4-4M20 16H6l4 4"/>',
    'recycle' => '<path d="M8 7l2-3h4l2 3M17 10l2 3-2 4h-3M7 10l-2 3 2 4h3"/>',
    'scissors' => '<circle cx="6" cy="7" r="3"/><circle cx="6" cy="17" r="3"/><path d="M8.5 8.5L20 19M8.5 15.5L20 5"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>', 'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>', 'back' => '<path d="M15 6l-6 6 6 6"/>',
    'check' => '<path d="M5 12l5 5 9-10"/>', 'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>', 'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>', 'list' => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
    'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>', 'edit' => '<path d="M4 20h4L20 8l-4-4L4 16z"/>', 'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'heart' => '<path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/>', 'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
    'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>', 'book' => '<path d="M4 5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5"/>',
    'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>', 'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
    'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>', 'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
    'sparkle' => '<path d="M12 4v4M12 16v4M4 12h4M16 12h4M6.3 6.3l2.5 2.5M15.2 15.2l2.5 2.5M6.3 17.7l2.5-2.5M15.2 8.8l2.5-2.5"/>',
    'team' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.5c2.5 0 4.5 1.5 5.5 4.5"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
];
function ic(string $name, string $cls = ''): string
{
    return '<svg class="ic' . ($cls ? ' ' . e($cls) : '') . '" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
        . (MEMORII_ICONS[$name] ?? '') . '</svg>';
}

/** Bundled campaign/catalog photo (AI-generated concept imagery shipped in public/assets/img). */
function img(string $key): string
{
    return '/assets/img/' . (preg_match('/^[a-z0-9]+$/', $key) ? $key : 'tote') . '.jpg';
}
const CATEGORY_IMG = ['jeans' => 'jeans', 'trousers' => 'jeans', 'denim_jacket' => 'jacket', 'jacket' => 'jacket', 'denim_shirt' => 'shirt', 'shirt' => 'shirt',
    'tshirt' => 'shirt', 'denim_skirt' => 'skirt', 'skirt' => 'skirt', 'dress' => 'dress', 'bag' => 'tote', 'other' => 'tote'];
const KIND_IMG = ['wear' => 'shirtdress', 'bag' => 'crossbody', 'acc' => 'accessories', 'home' => 'home'];
const KIND_ICON = ['wear' => 'hanger', 'bag' => 'box', 'acc' => 'star', 'home' => 'home'];
const PATH_ICON = ['redesign' => 'scissors', 'rewear' => 'tag', 'swap' => 'swap', 'recycle' => 'recycle', 'repair' => 'scissors', 'reuse' => 'hanger', 'upcycle' => 'sparkle'];
const CONDITION_TONE = ['excellent' => 'c-good', 'good' => 'c-good', 'fair' => 'c-fair', 'worn' => 'c-worn', 'damaged' => 'c-bad'];

function kind_label(string $k): string
{
    return ['wear' => L('เสื้อผ้า', 'Clothing'), 'bag' => L('กระเป๋า', 'Bags'), 'acc' => L('แอคเซสซอรี่', 'Accessories'), 'home' => L('ของแต่งบ้าน', 'Home')][$k] ?? $k;
}
/** Photo box: the member's own photo if there is one, otherwise a labelled sample for the category. */
function photo(?string $imageId, ?string $category, string $cls = '', string $alt = ''): string
{
    if ($imageId) return '<div class="ph ' . e($cls) . '"><img src="/media/garment/' . e($imageId) . '" alt="' . e($alt) . '" loading="lazy"></div>';
    return '<div class="ph ' . e($cls) . '"><img src="' . e(img(CATEGORY_IMG[$category ?? ''] ?? 'jeans')) . '" alt="" loading="lazy"><span class="sample">' . e(L('รูปตัวอย่าง', 'Sample')) . '</span></div>';
}
function img_box(string $src, string $cls = '', string $alt = ''): string
{
    return '<div class="ph ' . e($cls) . '"><img src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy"></div>';
}
function tpl_name(?array $tpl): string
{
    if (!$tpl) return t('studio.modeCustom');
    return I18n::locale() === 'th' ? $tpl['name_th'] : $tpl['name_en'];
}
function tpl_img(array $tpl): string { return img($tpl['image_key'] ?: 'tote'); }
function cond_badge(string $c): string
{
    return '<span class="cond static ' . (CONDITION_TONE[$c] ?? '') . '">' . e(t('condition.' . $c)) . '</span>';
}
/** Heart toggle (POST form, works without JS). */
function fav_button(string $target, bool $on, string $back): string
{
    return '<form method="post" action="/favorites" class="fav-form">' . csrf_field() . '<input type="hidden" name="target" value="' . e($target) . '"><input type="hidden" name="_back" value="' . e($back) . '">'
        . '<button class="fav' . ($on ? ' on' : '') . '" aria-pressed="' . ($on ? 'true' : 'false') . '" aria-label="' . e(L('บันทึกไว้ดูภายหลัง', 'Save for later')) . '">' . ic('heart') . '</button></form>';
}
function section_head(string $title, ?string $href = null, ?string $more = null): string
{
    return '<div class="sec-h"><h2>' . e($title) . '</h2>' . ($href ? '<a class="more" href="' . e($href) . '">' . e($more ?? L('ดูทั้งหมด', 'See all')) . ' ' . ic('arrow') . '</a>' : '') . '</div>';
}
function photo_banner(string $title, string $sub, string $imgKey = 'denim', string $extra = ''): string
{
    return '<section class="banner"><img class="banner-bg" src="' . e(img($imgKey)) . '" alt=""><div class="banner-in"><h1>' . e($title) . '</h1>'
        . ($sub !== '' ? '<p>' . e($sub) . '</p>' : '') . $extra . '</div></section>';
}
function listing_status_label(string $s): string
{
    return ['pending_review' => L('รอตรวจ', 'In review'), 'active' => L('เปิดอยู่', 'Live'), 'reserved' => L('จองแล้ว', 'Reserved'), 'sold' => L('ขายแล้ว', 'Sold'),
        'rejected' => L('ไม่ผ่านการตรวจ', 'Not approved'), 'withdrawn' => L('ถอนแล้ว', 'Withdrawn'), 'matched' => L('จับคู่แล้ว', 'Matched')][$s] ?? $s;
}
/** Percentage width as a CSS class (CSP forbids inline styles). */
function pct_class(int|float $pct): string
{
    return 'w' . (int) (round(max(0, min(100, $pct)) / 10) * 10);
}
