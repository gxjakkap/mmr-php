<?php use App\Core\View; View::$title = L('ตรวจประกาศ', 'Moderation'); ?>
<?= View::partial('admin/nav') ?>
<?= View::partial('partials/page_head', ['title' => L('ตรวจประกาศขายและแลก', 'Listing moderation'), 'body' => L('ตรวจว่ารูปเป็นของจริง ตรงกับคำอธิบาย และเปิดเผยตำหนิครบ ก่อนอนุมัติ', 'Check that photos are real, match the description and disclose every flaw before approving')]) ?>
<?php if (!$market && !$swaps): ?><?= View::partial('partials/empty', ['title' => L('ไม่มีประกาศรอตรวจ', 'Nothing to review')]) ?><?php endif; ?>
<div class="olist">
<?php foreach ($market as $l): ?><div class="ocard top"><a href="/market/<?= e($l['id']) ?>"><?= photo($l['cover_image_id'], $l['category'], 'mini') ?></a><div class="grow"><p class="xs muted">Rewear · <?= e(money((int) $l['price_satang'])) ?> · <?= e($l['seller_name']) ?> · <?= e(fmt_date($l['created_at'], true)) ?></p>
  <a href="/market/<?= e($l['id']) ?>"><strong><?= e($l['title']) ?></strong></a><p class="xs pre"><?= e($l['condition_disclosure']) ?></p></div>
  <div class="minw"><?= View::partial('partials/moderate_form', ['kind' => 'market', 'id' => $l['id'], 'back' => '/admin/moderation']) ?></div></div><?php endforeach; ?>
<?php foreach ($swaps as $l): ?><div class="ocard top"><a href="/swap/<?= e($l['id']) ?>"><?= photo($l['cover_image_id'], $l['category'], 'mini') ?></a><div class="grow"><p class="xs muted">Swap · <?= e($l['owner_name']) ?> · <?= e(fmt_date($l['created_at'], true)) ?></p>
  <a href="/swap/<?= e($l['id']) ?>"><strong><?= e($l['title']) ?></strong></a><p class="xs pre"><?= e($l['condition_disclosure']) ?></p></div>
  <div class="minw"><?= View::partial('partials/moderate_form', ['kind' => 'swap', 'id' => $l['id'], 'back' => '/admin/moderation']) ?></div></div><?php endforeach; ?>
</div>
