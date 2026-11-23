<?php use App\Core\{I18n, View}; $r = $d['request']; $g = $d['garment']; $tpl = $d['template']; $th = I18n::locale() === 'th'; ?>
<div class="card stack-sm">
  <div class="row gap-s"><?= View::partial('partials/status', ['status' => $r['status'], 'kind' => 'request']) ?>
    <?php if ($d['designer']): ?><span class="badge"><?= e(t('admin.assigned')) ?>: <?= e($d['designer']['public_name']) ?></span><?php endif; ?></div>
  <div class="grid sm-g2">
    <div>
      <p class="eyebrow"><?= e(t('studio.original')) ?></p>
      <p><strong><?= e($g['name']) ?></strong></p>
      <p class="xs muted"><?= e(t('category.' . $g['category'])) ?> · <?= e(t('condition.' . $g['condition'])) ?> · <?= e(t('fabric.' . $g['fabric_primary'])) ?></p>
      <?php if ($d['images']): ?><div class="scroll-x mt-s"><?php foreach (array_slice($d['images'], 0, 4) as $img): ?>
        <div class="thumb mini"><img src="/media/garment/<?= e($img['id']) ?>" alt="" loading="lazy"></div><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div>
      <p class="eyebrow"><?= e(t('studio.proposed')) ?></p>
      <p><strong><?= e($tpl ? ($th ? $tpl['name_th'] : $tpl['name_en']) : t('studio.modeCustom')) ?></strong></p>
      <?php if ($tpl): ?><p class="xs muted"><?= e(t('studio.priceRange')) ?>: ฿<?= number_format((int) $tpl['price_min_thb']) ?>–<?= number_format((int) $tpl['price_max_thb']) ?></p><?php endif; ?>
      <?php if ($r['budget_max_thb'] !== null): ?><p class="xs muted"><?= e(t('studio.budget')) ?>: ฿<?= number_format((int) $r['budget_max_thb']) ?></p><?php endif; ?>
    </div>
  </div>
  <?php if ($r['requirements'] !== ''): ?><div><p class="xs muted"><?= e(t('requests.requirementsLabel')) ?></p><p class="small pre"><?= e($r['requirements']) ?></p></div><?php endif; ?>
</div>
