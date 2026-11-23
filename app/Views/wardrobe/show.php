<?php use App\Core\{Upload, View}; View::$title = $g['name'];
$defects = json_list($g['defects']); $meas = json_list($g['measurements']); $comp = json_list($g['fabric_composition']);
$sel = min($sel, max(count($images) - 1, 0)); $gid = e($g['id']);
$top = $history['assessments'][0] ?? null; $topPath = $top ? (json_list($top['summary'])['top'][0] ?? null) : null;
$events = [[$g['created_at'], L('เพิ่มเข้าตู้เสื้อผ้า', 'Added to wardrobe'), null]];
foreach ($history['assessments'] as $a) $events[] = [$a['created_at'], t('assess.title') . ': ' . implode(' / ', array_map(fn($p) => t('pathway.' . $p), array_slice(json_list($a['summary'])['top'] ?? [], 0, 2))), '/assessments/' . $a['id']];
foreach ($history['requests'] as $r) $events[] = [$r['created_at'], $r['title'] . ' · ' . t('reqStatus.' . $r['status']), '/requests/' . $r['id']];
foreach ($listings as $l) $events[] = [$l['created_at'], L('ลงขาย Rewear', 'Listed on Rewear') . ' · ' . money((int) $l['price_satang']) . ' · ' . listing_status_label($l['status']), '/market/' . $l['id']];
foreach ($swaps as $s) $events[] = [$s['created_at'], L('ลงแลกเปลี่ยน', 'Listed for swap') . ' · ' . listing_status_label($s['status']), '/swap/' . $s['id']];
usort($events, fn($x, $y) => strcmp((string) $x[0], (string) $y[0])); ?>
<div class="w-md-l">
<div class="row between mb-m"><a class="btn btn-ghost btn-sm" href="/wardrobe"><?= ic('back') ?> <?= e(t('nav.wardrobe')) ?></a><a class="btn btn-outline btn-sm" href="/wardrobe/<?= $gid ?>/edit"><?= ic('edit') ?> <?= e(t('common.edit')) ?></a></div>
<div class="detail">
  <div>
    <?php if ($images): ?><div class="ph hero-ph"><img src="/media/garment/<?= e($images[$sel]['id']) ?>" alt="<?= e($g['name']) ?>"></div>
    <?php else: ?><?= photo(null, $g['category'], 'hero-ph') ?><?php endif; ?>
    <div class="thumbs"><?php foreach ($images as $i => $img): ?>
      <div class="stack-xs"><a class="th<?= $i === $sel ? ' on' : '' ?>" href="?img=<?= $i ?>" aria-label="<?= $i + 1 ?>"><img src="/media/garment/<?= e($img['id']) ?>" alt=""></a>
        <form method="post" action="/images/<?= e($img['id']) ?>/delete"><?= csrf_field() ?><button class="linkbtn xs" aria-label="<?= e(t('common.delete')) ?>"><?= e(t('common.delete')) ?></button></form></div>
    <?php endforeach; ?></div>
    <?php if (count($images) < 8): ?>
      <form method="post" action="/wardrobe/<?= $gid ?>/images" enctype="multipart/form-data" class="row mt-m">
        <?= csrf_field() ?>
        <input id="more-photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" aria-label="<?= e(t('wardrobe.addPhotos')) ?>"
          data-max-bytes="<?= Upload::MAX_BYTES ?>" data-max-files="<?= 8 - count($images) ?>" data-msg-type="<?= e(t('wardrobe.photoBadType')) ?>" data-msg-size="<?= e(t('wardrobe.photoTooBig')) ?>" data-msg-count="<?= e(t('wardrobe.tooManyPhotos')) ?>">
        <button class="btn btn-outline btn-sm"><?= ic('upload') ?> <?= e(t('wardrobe.addPhotos')) ?></button><span id="more-photos-error" class="hint" role="alert"></span>
      </form>
    <?php endif; ?>
  </div>
  <div class="stack">
    <div><h1 class="gname"><?= e($g['name']) ?></h1><div class="row mt-s"><?= cond_badge($g['condition']) ?><span class="muted small"><?= e(t('category.' . $g['category'])) ?> · <?= e(t('fabric.' . $g['fabric_primary'])) ?></span></div></div>
    <dl class="kv">
      <dt><?= e(t('wardrobe.composition')) ?></dt><dd><?= e(implode(', ', array_map(fn($c) => "{$c['pct']}% {$c['fiber']}", $comp)) ?: '—') ?></dd>
      <dt><?= e(t('wardrobe.color')) ?></dt><dd><?= e($g['color'] ?: '—') ?></dd>
      <dt><?= e(t('wardrobe.measurements')) ?></dt><dd><?= e(implode(' · ', array_map(fn($k, $v) => t('measure.' . $k) . " {$v}", array_keys($meas), $meas)) ?: '—') ?></dd>
      <dt><?= e(t('wardrobe.defects')) ?></dt><dd><?= e(implode(', ', array_map(fn($d) => t('defect.' . $d), $defects)) ?: '—') ?></dd>
    </dl>
    <?php if ($g['condition_notes']): ?><p class="small pre"><?= e($g['condition_notes']) ?></p><?php endif; ?>
    <?php if ($g['sentimental_notes']): ?><blockquote class="story">“<?= e($g['sentimental_notes']) ?>”</blockquote><?php endif; ?>
    <?php if ($topPath): ?><a class="hint-card" href="/assessments/<?= e($top['id']) ?>"><span class="pi"><?= ic(PATH_ICON[$topPath] ?? 'leaf') ?></span><span class="grow"><span class="xs muted"><?= e(L('ผลประเมินล่าสุดแนะนำ', 'Latest assessment suggests')) ?></span><br><b><?= e(t('pathway.' . $topPath)) ?></b></span><?= ic('arrow') ?></a><?php endif; ?>
    <div class="duo"><a class="btn btn-outline btn-lg" href="/wardrobe/<?= $gid ?>/assess"><?= ic('leaf') ?> <?= e(L('ประเมินศักยภาพ', 'Assess')) ?></a><a class="btn btn-gold btn-lg" href="<?= e(url('/studio', ['garment' => $g['id']])) ?>"><?= ic('studio') ?> <?= e(L('ออกแบบใหม่', 'Redesign')) ?></a></div>
    <div class="duo"><a class="btn btn-outline" href="/wardrobe/<?= $gid ?>/sell"><?= ic('tag') ?> <?= e(L('ลงขายมือสอง', 'Sell (Rewear)')) ?></a><a class="btn btn-outline" href="/wardrobe/<?= $gid ?>/swap"><?= ic('swap') ?> <?= e(L('ลงแลกเปลี่ยน', 'List for swap')) ?></a></div>
    <div><p class="lbl"><?= e(L('ประวัติการใช้งาน', 'History')) ?></p><ol class="timeline"><?php foreach ($events as [$at, $txt, $href]): ?>
      <li><span class="xs muted"><?= e(fmt_date($at)) ?></span><br><?= $href ? '<a href="' . e($href) . '">' . e($txt) . '</a>' : e($txt) ?></li><?php endforeach; ?></ol></div>
  </div>
</div>
<form method="post" action="/wardrobe/<?= $gid ?>/delete" class="row danger-zone" aria-label="<?= e(t('common.delete')) ?>">
  <?= csrf_field() ?>
  <label class="check xs muted"><input type="checkbox" name="confirm" value="1" required> <?= e(t('wardrobe.deleteConfirm')) ?></label>
  <button class="btn btn-danger btn-sm"><?= ic('trash') ?> <?= e(t('common.delete')) ?></button>
</form>
</div>
