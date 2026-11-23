<?php use App\Core\View; use App\Services\Validate; View::$title = t('wardrobe.title');
$ready = fn($g) => isset($assessed[$g['id']]) && !isset($inRequest[$g['id']]);
$active = fn($g) => isset($inRequest[$g['id']]) || isset($listed[$g['id']]);
$list = array_values(array_filter($all, fn($g) => ($q === '' || mb_stripos($g['name'] . ' ' . t('category.' . $g['category']) . ' ' . $g['color'], $q) !== false)
  && (!$category || $g['category'] === $category) && ($filter !== 'ready' || $ready($g)) && ($filter !== 'active' || $active($g))));
$nReady = count(array_filter($all, $ready)); $nActive = count(array_filter($all, $active));
$link = fn(array $p) => url('/wardrobe', $p + ['q' => $q, 'category' => $category, 'f' => $filter === 'all' ? null : $filter]); ?>
<div class="withside"><?= View::partial('partials/side_nav', ['cur' => 'wardrobe']) ?><div>
<?= photo_banner('My Wardrobe', L('ตู้เสื้อผ้าของฉัน · จัดการเสื้อผ้า ประเมินศักยภาพ และต่อชีวิตด้วยไอเดียใหม่', 'Manage garments, assess their potential and give them a new life'), 'denim') ?>
<div class="stats">
  <?php foreach ([['hanger', count($all), L('ชิ้นทั้งหมด', 'items')], ['leaf', $nReady, L('ประเมินแล้ว พร้อมต่อชีวิต', 'assessed, ready')], ['clock', $nActive, L('กำลังดำเนินการ', 'in progress')]] as [$i, $n, $l]): ?>
    <div class="stat"><span class="stat-ic"><?= ic($i) ?></span><div><b><?= $n ?></b><span><?= e($l) ?></span></div></div><?php endforeach; ?>
  <a class="btn btn-gold stat-cta" href="/wardrobe/new"><?= ic('plus') ?> <?= e(t('wardrobe.add')) ?></a></div>
<form class="tools" method="get" action="/wardrobe" role="search">
  <label class="search"><?= ic('search') ?><input name="q" value="<?= e($q) ?>" placeholder="<?= e(L('ค้นหาเสื้อผ้า (เช่น แจ็กเก็ต, ยีนส์, เสื้อเชิ้ต)', 'Search garments (jacket, jeans, shirt…)')) ?>" aria-label="<?= e(L('ค้นหา', 'Search')) ?>"></label>
  <select class="input sel-auto" name="category" aria-label="<?= e(t('wardrobe.filterCategory')) ?>"><option value=""><?= e(t('wardrobe.category')) ?>: <?= e(t('common.all')) ?></option>
    <?php foreach (Validate::CATEGORIES as $c): ?><option value="<?= e($c) ?>"<?= $c === $category ? ' selected' : '' ?>><?= e(t('category.' . $c)) ?></option><?php endforeach; ?></select>
  <?php if ($filter !== 'all'): ?><input type="hidden" name="f" value="<?= e($filter) ?>"><?php endif; ?>
  <button class="btn btn-outline"><?= e(L('ค้นหา', 'Search')) ?></button></form>
<div class="pills"><?php foreach ([['all', t('common.all') . ' (' . count($all) . ')'], ['ready', L('พร้อมต่อชีวิต', 'Ready') . " ({$nReady})"], ['active', L('กำลังดำเนินการ', 'In progress') . " ({$nActive})"]] as [$k, $l]): ?>
  <a class="btn btn-chip" href="<?= e($link(['f' => $k === 'all' ? null : $k])) ?>"<?= $filter === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></div>
<?php if ($list): ?><div class="gcards"><?php foreach ($list as $g):
  $st = isset($inRequest[$g['id']]) ? ['tone-gold', L('กำลังรีดีไซน์', 'In redesign')] : (isset($listed[$g['id']]) ? ['tone-gold', L('ลงขาย/แลกอยู่', 'Listed')] : (isset($assessed[$g['id']]) ? ['tone-sage', L('ประเมินแล้ว', 'Assessed')] : null)); ?>
  <a class="gcard" href="/wardrobe/<?= e($g['id']) ?>"><?= photo($g['cover_image_id'], $g['category'], '', $g['name']) ?><span class="cond <?= CONDITION_TONE[$g['condition']] ?? '' ?>"><?= e(t('condition.' . $g['condition'])) ?></span>
    <div class="gc-b"><strong><?= e($g['name']) ?></strong><span class="xs muted"><?= e(t('category.' . $g['category'])) ?><?= $g['color'] ? ' · ' . e($g['color']) : '' ?></span>
      <?php if ($st): ?><span class="badge <?= $st[0] ?> mt-s"><?= e($st[1]) ?></span><?php endif; ?></div></a>
<?php endforeach; ?></div>
<?php else: ?><div class="mt-m"><?= View::partial('partials/empty', ['title' => $all ? L('ไม่พบเสื้อผ้าที่ตรงกับการค้นหา', 'No garments match your search') : t('wardrobe.emptyTitle'), 'body' => $all ? '' : t('wardrobe.emptyBody'),
  'action' => $all ? '' : '<a class="btn btn-gold" href="/wardrobe/new">' . ic('plus') . ' ' . e(t('wardrobe.add')) . '</a>']) ?></div><?php endif; ?>
<section class="promo"><h3><?= e(L('ต่อชีวิตเสื้อผ้าชิ้นถัดไป', 'Give your next garment a new life')) ?></h3>
  <div class="promo-row">
    <a class="promo-i" href="<?= $list ? '/wardrobe/' . e($list[0]['id']) . '/assess' : '/wardrobe/new' ?>"><span class="pi"><?= ic('leaf') ?></span><span class="grow"><b>Second-Life Assessment</b><br><span class="xs muted"><?= e(t('assess.title')) ?></span></span><?= ic('arrow') ?></a>
    <a class="promo-i" href="/studio"><span class="pi"><?= ic('studio') ?></span><span class="grow"><b>Creative Studio</b><br><span class="xs muted"><?= e(L('เสื้อผ้า กระเป๋า ของใช้', 'Clothing, bags, accessories')) ?></span></span><?= ic('arrow') ?></a>
    <a class="promo-i" href="/style"><span class="pi"><?= ic('sparkle') ?></span><span class="grow"><b>Outfit Planner</b><br><span class="xs muted"><?= e(L('จัดชุดจากตู้ของคุณ', 'Outfits from your closet')) ?></span></span><?= ic('arrow') ?></a></div></section>
</div></div>
