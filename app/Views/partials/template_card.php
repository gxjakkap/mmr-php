<?php
/** @var array $tpl @var ?string $href @var bool $selected */
use App\Core\I18n;
$th = I18n::locale() === 'th';
$tone = ['high' => 'tone-danger', 'medium' => 'tone-gold', 'low' => 'tone-sage'][$tpl['difficulty']] ?? '';
$note = $th ? $tpl['feasibility_note_th'] : $tpl['feasibility_note_en'];
$tag = !empty($href) ? 'a' : 'div';
?>
<<?= $tag ?> class="card<?= !empty($selected) ? ' selected' : '' ?>"<?= !empty($href) ? ' href="' . e($href) . '"' : '' ?><?= !empty($selected) ? ' aria-current="true"' : '' ?>>
  <div class="row between top">
    <h3><?= e($th ? $tpl['name_th'] : $tpl['name_en']) ?></h3>
    <span class="badge <?= $tone ?>"><?= e(t('studio.difficulty')) ?>: <?= e(t('level.' . $tpl['difficulty'])) ?></span>
  </div>
  <p class="small muted mt-s"><?= e($th ? $tpl['description_th'] : $tpl['description_en']) ?></p>
  <dl class="kv xs mt-m">
    <dt><?= e(t('studio.priceRange')) ?></dt><dd>฿<?= number_format((int) $tpl['price_min_thb']) ?>–<?= number_format((int) $tpl['price_max_thb']) ?></dd>
    <dt><?= e(t('studio.extraMaterials')) ?></dt><dd><?= e(implode(', ', json_list($tpl['extra_materials'])) ?: '—') ?></dd>
    <dt><?= e(t('studio.compatible')) ?></dt><dd><?= e(implode(', ', array_map(fn($c) => t('category.' . $c), json_list($tpl['compatible_categories'])))) ?></dd>
  </dl>
  <?php if ($note !== ''): ?><p class="alert xs mt-m"><?= e(t('studio.feasibility')) ?>: <?= e($note) ?></p><?php endif; ?>
</<?= $tag ?>>
