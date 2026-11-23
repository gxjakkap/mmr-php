<?php use App\Core\View; use App\Domain\AssessmentEngine; \App\Core\View::$title = t('assess.title'); ?>
<div class="w-sm">
<?= View::partial('partials/page_head', ['eyebrow' => $g['name'], 'title' => t('assess.title'), 'body' => t('assess.intro')]) ?>
<p class="alert mb-m"><?= e(t('assess.preliminary')) ?></p>
<div class="card">
  <form method="post" action="/wardrobe/<?= e($g['id']) ?>/assess" class="stack-sm">
    <?= csrf_field() ?>
    <label class="field"><span><?= e(t('assess.outcome')) ?></span><select class="input" name="desiredOutcome">
      <?php foreach (AssessmentEngine::OUTCOMES as $o): ?><option value="<?= e($o) ?>"><?= e(t('outcome.' . $o)) ?></option><?php endforeach; ?></select></label>
    <label class="field"><span><?= e(t('assess.budget')) ?></span><input class="input" type="number" name="budgetMaxThb" min="0" max="1000000" inputmode="numeric"><span class="hint"><?= e(t('common.optional')) ?></span></label>
    <label class="check"><input type="checkbox" name="hasExtraMaterial" value="1"> <?= e(t('assess.extraMaterial')) ?></label>
    <label class="field"><span><?= e(t('assess.preferences')) ?></span><textarea class="input" name="preferences" maxlength="1000"></textarea></label>
    <button class="btn btn-block"><?= e(t('assess.run')) ?></button>
  </form>
</div>
</div>
