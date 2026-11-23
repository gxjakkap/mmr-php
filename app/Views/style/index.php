<?php use App\Core\View; View::$title = 'Outfit Planner';
$strip = fn(array $items) => '<div class="ostrip">' . implode('', array_map(fn($g) => photo($g['cover_image_id'], $g['category'], '', $g['name']), $items)) . '</div>';
$why = ['double_denim' => L('เดนิมทั้งชุด — ลองคนละเฉดให้ตัดกัน', 'Double denim — mix two washes for contrast'), 'top_bottom' => L('ท่อนบน + ท่อนล่าง จากตู้ของคุณ', 'A top and a bottom from your closet'),
  'dress_layer' => L('เดรสคลุมทับด้วยแจ็กเก็ต', 'Dress layered with a jacket'), 'one_piece' => L('เดรสตัวเดียวจบ', 'One-piece look')]; ?>
<div class="withside"><?= View::partial('partials/side_nav', ['cur' => 'style']) ?><div>
<?= photo_banner('Outfit Planner', L('จัดชุดจากเสื้อผ้าที่มีอยู่แล้ว — ใส่ซ้ำอย่างมีสไตล์ คือความยั่งยืนที่ง่ายที่สุด', 'Build outfits from what you own — rewearing in style is the easiest sustainability'), 'model2') ?>
<section class="sec"><?= section_head(L('ไอเดียจากตู้ของคุณ', 'Ideas from your closet')) ?><p class="xs muted mb-m"><?= e(L('แนะนำด้วยกฎง่าย ๆ (ท่อนบน + ท่อนล่าง, เดรส + แจ็กเก็ต) ยังไม่ใช่ AI สไตลิสต์', 'Suggested by simple rules (top + bottom, dress + jacket), not an AI stylist')) ?></p>
<?php if ($ideas): ?><div class="outfits"><?php foreach ($ideas as $o): ?><div class="outfit"><?= $strip($o['items']) ?><p class="small"><?= e($why[$o['why']]) ?></p>
  <form method="post" action="/style/outfits"><?= csrf_field() ?><?php foreach ($o['items'] as $g): ?><input type="hidden" name="garments[]" value="<?= e($g['id']) ?>"><?php endforeach; ?><input type="hidden" name="name" value=""><button class="btn btn-outline btn-sm"><?= ic('heart') ?> <?= e(L('บันทึกชุดนี้', 'Save outfit')) ?></button></form></div><?php endforeach; ?></div>
<?php else: ?><?= View::partial('partials/empty', ['title' => L('ยังจัดชุดไม่ได้', 'No outfits yet'), 'body' => L('เพิ่มท่อนบนและท่อนล่างอย่างน้อยอย่างละ 1 ชิ้น', 'Add at least one top and one bottom'), 'action' => '<a class="btn btn-gold" href="/wardrobe/new">' . ic('plus') . ' ' . e(t('wardrobe.add')) . '</a>']) ?><?php endif; ?></section>
<?php if (count($garments) >= 2): ?><section class="sec"><?= section_head(L('จัดชุดเอง', 'Build your own')) ?>
  <form method="post" action="/style/outfits" class="stack-sm"><?= csrf_field() ?><div class="rail"><?php foreach ($garments as $g): ?>
    <label class="pickg"><input class="sr-only" type="checkbox" name="garments[]" value="<?= e($g['id']) ?>"><?= photo($g['cover_image_id'], $g['category']) ?><strong><?= e($g['name']) ?></strong></label><?php endforeach; ?></div>
    <div class="row"><input class="input grow" name="name" maxlength="60" placeholder="<?= e(L('ชื่อชุด เช่น ไปคาเฟ่วันเสาร์', 'Outfit name, e.g. Saturday café')) ?>" aria-label="<?= e(L('ชื่อชุด', 'Outfit name')) ?>"><button class="btn"><?= e(L('บันทึกชุด (2–8 ชิ้น)', 'Save outfit (2–8 pieces)')) ?></button></div></form></section><?php endif; ?>
<section class="sec"><?= section_head(L('ชุดที่บันทึกไว้', 'Saved outfits')) ?><?php if ($outfits): ?><div class="outfits"><?php foreach ($outfits as $o): ?><div class="outfit"><?= $strip($o['items']) ?>
  <p class="small"><b><?= e($o['name'] ?: L('ชุดของฉัน', 'My outfit')) ?></b><br><span class="xs muted"><?= e(fmt_date($o['created_at'])) ?></span></p>
  <form method="post" action="/outfits/<?= e($o['id']) ?>/delete"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= ic('trash') ?> <?= e(t('common.delete')) ?></button></form></div><?php endforeach; ?></div>
<?php else: ?><p class="muted small">—</p><?php endif; ?></section></div></div>
