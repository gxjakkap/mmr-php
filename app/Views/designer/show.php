<?php use App\Core\{I18n, View}; use App\Domain\{Money, Workflow};
$r = $d['request']; $g = $d['garment']; $tpl = $d['template']; \App\Core\View::$title = $r['title']; $self = '/designer/requests/' . $r['id'];
$moves = Workflow::nextRequest($r['status'], ['designer']);
$items = $old['items'] ?? [['kind' => 'design_fee'], ['kind' => 'labor'], ['kind' => 'material'], ['kind' => 'other']];
$meas = json_list($g['measurements']); ?>
<div class="stack">
<?= View::partial('partials/page_head', ['eyebrow' => t('designer.title'), 'title' => $r['title']]) ?>
<div class="grid lg-split top">
  <div class="stack">
    <?= View::partial('partials/request/summary', ['d' => $d]) ?>
    <div class="card small"><p><?= e(t('wardrobe.defects')) ?>: <?= e(implode(', ', array_map(fn($x) => t('defect.' . $x), json_list($g['defects']))) ?: '—') ?></p>
      <p><?= e(t('wardrobe.measurements')) ?>: <?= e(implode(' · ', array_map(fn($k, $v) => t('measure.' . $k) . " {$v}", array_keys($meas), $meas)) ?: '—') ?></p></div>
    <div class="card"><h3><?= e(t('designer.proposalForm')) ?></h3>
      <?php if ($r['status'] !== 'QUOTE_PREPARING'): ?><p class="alert mt-m"><?= e(t('designer.notReady')) ?></p><?php else: ?>
      <form method="post" action="<?= e($self) ?>/proposal" class="stack-sm mt-m" data-quote data-vat-bp="<?= Money::VAT_BP ?>" data-locale="<?= e(I18n::locale()) ?>">
        <?= csrf_field() ?>
        <label class="field"><span><?= e(t('designer.description')) ?></span><textarea class="input" name="description" required minlength="10" maxlength="6000"><?= e($old['description'] ?? '') ?></textarea></label>
        <div class="grid sm-g2">
          <label class="field"><span><?= e(t('studio.difficulty')) ?></span><select class="input" name="difficulty"><?php foreach (['low', 'medium', 'high'] as $l): ?><option value="<?= $l ?>"<?= ($old['difficulty'] ?? $tpl['difficulty'] ?? 'medium') === $l ? ' selected' : '' ?>><?= e(t('level.' . $l)) ?></option><?php endforeach; ?></select></label>
          <label class="field"><span><?= e(t('designer.validDays')) ?></span><input class="input" type="number" name="valid_days" min="1" max="60" value="<?= e($old['valid_days'] ?? '14') ?>"></label>
        </div>
        <label class="field"><span><?= e(t('designer.materials')) ?></span><input class="input" name="materials" maxlength="1000" value="<?= e($old['materials'] ?? ($tpl ? implode(', ', json_list($tpl['extra_materials'])) : '')) ?>"></label>
        <label class="field"><span><?= e(t('requests.feasibilityNotes')) ?></span><textarea class="input" name="feasibility_notes" maxlength="4000"><?= e($old['feasibility_notes'] ?? '') ?></textarea></label>
        <fieldset class="stack-sm"><legend class="label"><?= e(t('designer.items')) ?></legend>
          <?php foreach (array_values($items) as $i => $it): ?>
          <div class="item-row">
            <label class="field"><span><?= e(t('designer.kind')) ?></span><select class="input" data-f="kind" id="k<?= $i ?>" name="items[<?= $i ?>][kind]"><?php foreach (Money::KINDS as $k): ?><option value="<?= $k ?>"<?= ($it['kind'] ?? '') === $k ? ' selected' : '' ?>><?= e(t('quoteKind.' . $k)) ?></option><?php endforeach; ?></select></label>
            <label class="field"><span><?= e(t('designer.itemDescription')) ?></span><input class="input" id="d<?= $i ?>" name="items[<?= $i ?>][description]" maxlength="300" value="<?= e($it['description'] ?? '') ?>"></label>
            <label class="field"><span><?= e(t('designer.qty')) ?></span><input class="input" data-f="qty" id="q<?= $i ?>" type="number" step="0.5" min="0.5" name="items[<?= $i ?>][quantity]" value="<?= e($it['quantity'] ?? '1') ?>"></label>
            <label class="field"><span><?= e(t('designer.unitPrice')) ?></span><input class="input" data-f="price" id="p<?= $i ?>" type="number" step="0.01" min="0" name="items[<?= $i ?>][unit_price_thb]" value="<?= e($it['unit_price_thb'] ?? '') ?>"></label>
          </div>
          <?php endforeach; ?>
          <button type="button" class="btn btn-outline btn-sm" data-add-row hidden><?= e(t('designer.addItem')) ?></button>
        </fieldset>
        <div class="alert small tabular"><p class="xs muted"><?= e(t('designer.liveTotal')) ?></p>
          <p><?= e(t('requests.subtotal')) ?> <span data-out="subtotal">—</span> · <?= e(t('requests.discount')) ?> <span data-out="discount">—</span> · <?= e(t('requests.vat')) ?> <span data-out="tax">—</span></p>
          <p><strong><?= e(t('requests.total')) ?> <span data-out="total">—</span></strong></p>
          <p class="xs tone-danger" data-out="warn" hidden>discount &gt; subtotal</p></div>
        <label class="field"><span><?= e(t('common.notes')) ?></span><textarea class="input" name="notes" maxlength="4000"><?= e($old['notes'] ?? '') ?></textarea></label>
        <button class="btn"><?= e(t('designer.submit')) ?></button>
      </form>
      <?php endif; ?>
    </div>
    <?php if ($moves): ?><div class="row"><?= View::partial('partials/transition_buttons', ['moves' => $moves, 'action' => "/requests/{$r['id']}/transition", 'back' => $self, 'kind' => 'request', 'withNote' => true,
      'labels' => ['NEEDS_INFORMATION' => t('designer.requestInfo'), 'QUOTE_PREPARING' => t('designer.reopen')]]) ?></div><?php endif; ?>
    <?php foreach ($d['quotations'] as $q): $v = array_values(array_filter($d['versions'], fn($x) => $x['id'] === $q['design_version_id']))[0] ?? null; ?>
      <div class="card"><?= View::partial('partials/request/quotation', ['q' => $q, 'v' => $v]) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="stack">
    <?= View::partial('partials/request/messages', ['d' => $d, 'viewerId' => $actor->id, 'back' => $self, 'allowInternal' => true]) ?>
    <?= View::partial('partials/request/timeline', ['d' => $d]) ?>
  </div>
</div>
</div>
