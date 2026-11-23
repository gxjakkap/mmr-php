<?php
use App\Core\{Upload, View};
use App\Services\Validate;
use App\Domain\AssessmentEngine;
/** @var array $g @var ?string $id */
\App\Core\View::$title = $id ? t('wardrobe.editTitle') : t('wardrobe.addTitle');
$defects = is_array($g['defects'] ?? null) ? $g['defects'] : json_list($g['defects'] ?? '[]');
$meas = is_array($g['measurements'] ?? null) ? $g['measurements'] : json_list($g['measurements'] ?? '{}');
$sel = fn($a, $b) => $a === $b ? ' selected' : '';
$remaining = 8 - (int) ($imageCount ?? 0);
?>
<div class="w-md">
<?= View::partial('partials/page_head', ['title' => \App\Core\View::$title]) ?>
<form method="post" action="<?= $id ? '/wardrobe/' . e($id) : '/wardrobe' ?>" enctype="multipart/form-data" class="stack">
  <?= csrf_field() ?>
  <div class="card stack-sm">
    <label class="field"><span><?= e(t('wardrobe.name')) ?></span><input class="input" name="name" required maxlength="120" value="<?= e($g['name'] ?? '') ?>"></label>
    <div class="grid sm-g2">
      <label class="field"><span><?= e(t('wardrobe.category')) ?></span><select class="input" name="category">
        <?php foreach (Validate::CATEGORIES as $c): ?><option value="<?= e($c) ?>"<?= $sel($c, $g['category'] ?? '') ?>><?= e(t('category.' . $c)) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span><?= e(t('wardrobe.fabric')) ?></span><select class="input" name="fabric_primary">
        <?php foreach (Validate::FABRICS as $f): ?><option value="<?= e($f) ?>"<?= $sel($f, $g['fabric_primary'] ?? '') ?>><?= e(t('fabric.' . $f)) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span><?= e(t('wardrobe.composition')) ?></span><input class="input" name="composition_text" maxlength="200" value="<?= e($g['composition_text'] ?? '') ?>"><span class="hint"><?= e(t('wardrobe.compositionHint')) ?></span></label>
      <label class="field"><span><?= e(t('wardrobe.color')) ?></span><input class="input" name="color" maxlength="40" value="<?= e($g['color'] ?? '') ?>"></label>
    </div>
  </div>
  <div class="card stack-sm">
    <label class="field"><span><?= e(t('wardrobe.condition')) ?></span><select class="input" name="condition">
      <?php foreach (AssessmentEngine::CONDITIONS as $c): ?><option value="<?= e($c) ?>"<?= $sel($c, $g['condition'] ?? '') ?>><?= e(t('condition.' . $c)) ?></option><?php endforeach; ?></select></label>
    <fieldset class="chips" aria-label="<?= e(t('wardrobe.defects')) ?>"><legend class="label"><?= e(t('wardrobe.defects')) ?></legend>
      <?php foreach (Validate::DEFECTS as $d): ?>
        <label><input type="checkbox" name="defects[]" value="<?= e($d) ?>"<?= in_array($d, $defects, true) ? ' checked' : '' ?>><span><?= e(t('defect.' . $d)) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <label class="field"><span><?= e(t('wardrobe.conditionNotes')) ?></span><textarea class="input" name="condition_notes" maxlength="2000"><?= e($g['condition_notes'] ?? '') ?></textarea></label>
  </div>
  <div class="card">
    <p class="label"><?= e(t('wardrobe.measurements')) ?></p>
    <div class="grid g2 sm-g4">
      <?php foreach (Validate::MEASUREMENTS as $k): ?>
        <label class="field"><span><?= e(t('measure.' . $k)) ?></span><input class="input" type="number" inputmode="decimal" min="1" max="400" step="0.5" name="measurements[<?= e($k) ?>]" value="<?= e($meas[$k] ?? '') ?>"></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card stack-sm">
    <label class="field"><span><?= e(t('wardrobe.sentimental')) ?></span><textarea class="input" name="sentimental_notes" maxlength="2000"><?= e($g['sentimental_notes'] ?? '') ?></textarea></label>
    <?php if ($remaining > 0): ?>
    <label class="field"><span><?= e(t('wardrobe.photos')) ?></span>
      <input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" capture="environment"
        data-max-bytes="<?= Upload::MAX_BYTES ?>" data-max-files="<?= $remaining ?>" data-msg-type="<?= e(t('wardrobe.photoBadType')) ?>"
        data-msg-size="<?= e(t('wardrobe.photoTooBig')) ?>" data-msg-count="<?= e(t('wardrobe.tooManyPhotos')) ?>">
      <span class="hint"><?= e(t('wardrobe.photosHint')) ?></span><span id="photos-error" class="hint" role="alert"></span></label>
    <?php endif; ?>
  </div>
  <div class="row">
    <button class="btn"><?= e(t('common.save')) ?></button>
    <a class="btn btn-ghost" href="<?= $id ? '/wardrobe/' . e($id) : '/wardrobe' ?>"><?= e(t('common.cancel')) ?></a>
  </div>
</form>
</div>
