<?php use App\Core\View; View::$title = L('ที่บันทึกไว้', 'Saved'); ?>
<div class="withside"><?= View::partial('partials/side_nav', ['cur' => 'saved']) ?><div>
<h1><?= e(L('ที่บันทึกไว้', 'Saved')) ?></h1><p class="muted small mt-s"><?= e(L('แบบที่ชอบและสินค้าที่สนใจ', 'Designs and items you liked')) ?></p>
<?php if ($templates): ?><section class="sec"><?= section_head(L('แบบรีดีไซน์', 'Redesign ideas')) ?><div class="mgrid"><?php foreach ($templates as $tp): ?>
  <div class="mcard"><a href="/studio?template=<?= e($tp['id']) ?>"><?= img_box(tpl_img($tp), 'tall') ?></a><?= fav_button('t:' . $tp['slug'], true, '/saved') ?>
    <a class="mc-b" href="/studio?template=<?= e($tp['id']) ?>"><strong><?= e(tpl_name($tp)) ?></strong><span class="xs muted"><?= e(kind_label($tp['kind'])) ?> · ฿<?= number_format((int) $tp['price_min_thb']) ?>–<?= number_format((int) $tp['price_max_thb']) ?></span></a></div><?php endforeach; ?></div></section><?php endif; ?>
<?php if ($items): ?><section class="sec"><?= section_head(L('สินค้ามือสอง', 'Rewear items')) ?><div class="mgrid"><?php foreach ($items as $l) echo View::partial('partials/market_card', ['l' => $l, 'favs' => $favs, 'back' => '/saved']); ?></div></section><?php endif; ?>
<?php if (!$templates && !$items): ?><div class="empty mt-l"><img class="empty-img" src="<?= e(img('dress')) ?>" alt=""><p class="title"><?= e(L('ยังไม่มีรายการที่บันทึก', 'Nothing saved yet')) ?></p><p class="small muted mt-s"><?= e(L('กดรูปหัวใจบนแบบหรือสินค้าที่ชอบ', 'Tap the heart on designs or items you like')) ?></p><a class="btn btn-gold mt-l" href="/studio"><?= e(L('ดูแบบรีดีไซน์', 'Browse designs')) ?></a></div><?php endif; ?>
</div></div>
