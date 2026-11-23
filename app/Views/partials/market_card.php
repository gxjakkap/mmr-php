<?php /** @var array $l listing row @var array $favs @var string $back */ ?>
<div class="mcard">
  <a href="/market/<?= e($l['id']) ?>"><?= photo($l['cover_image_id'], $l['category'], 'tall', $l['title']) ?></a>
  <?= fav_button('m:' . $l['id'], in_array('m:' . $l['id'], $favs, true), $back) ?>
  <?php if ($l['status'] === 'reserved'): ?><span class="ribbon"><?= e(listing_status_label('reserved')) ?></span><?php endif; ?>
  <a class="mc-b" href="/market/<?= e($l['id']) ?>"><strong><?= e($l['title']) ?></strong>
    <span class="xs muted"><?= e(t('category.' . $l['category'])) ?><?= $l['size_label'] ? ' · ' . e($l['size_label']) : '' ?></span>
    <span class="price tabular"><?= e(money((int) $l['price_satang'])) ?></span></a>
</div>
