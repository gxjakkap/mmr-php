<?php
/** @var array $q quotation with items @var ?array $v design version */
?>
<?php if ($v): ?>
<div class="stack-sm small">
  <div class="row gap-s"><strong><?= e(t('requests.proposal')) ?> · <?= e(t('requests.revision')) ?> <?= (int) $v['version_no'] ?></strong>
    <span class="badge <?= $v['difficulty'] === 'high' ? 'tone-danger' : 'tone-gold' ?>"><?= e(t('studio.difficulty')) ?>: <?= e(t('level.' . $v['difficulty'])) ?></span>
    <?php if ($v['is_ai_concept']): ?><span class="badge tone-danger">AI concept</span><?php endif; ?></div>
  <p class="pre"><?= e($v['description']) ?></p>
  <?php if ($m = json_list($v['materials'])): ?><p class="xs"><span class="muted"><?= e(t('studio.extraMaterials')) ?>:</span> <?= e(implode(', ', $m)) ?></p><?php endif; ?>
  <?php if ($v['feasibility_notes'] !== ''): ?><p class="alert xs"><?= e(t('requests.feasibilityNotes')) ?>: <?= e($v['feasibility_notes']) ?></p><?php endif; ?>
</div>
<?php endif; ?>
<table class="small tabular mt-m">
  <tbody>
  <?php foreach ($q['items'] as $i): ?>
    <tr><td><span class="xs muted"><?= e(t('quoteKind.' . $i['kind'])) ?></span><br><?= e($i['description']) ?><?= (float) $i['quantity'] != 1 ? ' × ' . e((float) $i['quantity']) : '' ?></td>
      <td class="right"><?= $i['kind'] === 'discount' ? '−' : '' ?><?= e(money((int) $i['line_total_satang'])) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td class="muted"><?= e(t('requests.subtotal')) ?></td><td class="right"><?= e(money((int) $q['subtotal_satang'])) ?></td></tr>
    <?php if ((int) $q['discount_satang'] > 0): ?><tr><td class="muted"><?= e(t('requests.discount')) ?></td><td class="right">−<?= e(money((int) $q['discount_satang'])) ?></td></tr><?php endif; ?>
    <tr><td class="muted"><?= e(t('requests.vat')) ?></td><td class="right"><?= e(money((int) $q['tax_satang'])) ?></td></tr>
    <tr class="total-row"><td><?= e(t('requests.total')) ?></td><td class="right"><?= e(money((int) $q['total_satang'])) ?></td></tr>
  </tfoot>
</table>
<p class="xs muted mt-s"><?= e(t('requests.expires')) ?>: <?= e(fmt_date($q['expires_at'], true)) ?> · <?= e(t('requests.revision')) ?> <?= (int) $q['revision_no'] ?> · <span class="badge"><?= e($q['status']) ?></span></p>
<?php if ($q['notes']): ?><p class="xs pre mt-s"><?= e($q['notes']) ?></p><?php endif; ?>
