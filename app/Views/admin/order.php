<?php use App\Core\View; use App\Domain\Workflow;
$o = $d['order']; \App\Core\View::$title = $o['order_no']; $self = '/admin/orders/' . $o['id'];
$moves = Workflow::nextOrder($o['status'], ['admin']); ?>
<?= View::partial('admin/nav') ?>
<div class="stack">
<?= View::partial('partials/page_head', ['eyebrow' => $o['order_no'], 'title' => (string) $d['title'], 'action' => View::partial('partials/status', ['status' => $o['status'], 'kind' => 'order'])]) ?>
<p class="small"><a href="/admin/requests/<?= e($o['request_id']) ?>"><?= e(t('admin.requests')) ?></a> · <?= e(t('requests.total')) ?> <?= e(money((int) $o['total_satang'])) ?> · <?= e(t('orders.paidTotal')) ?> <?= e(money($d['paid'])) ?></p>
<?php $slips = (new App\Services\PaymentService(App\Core\Db::get()))->slips($o['id']); if ($slips): ?>
<div class="card"><p class="lbl"><?= e(L('สลิปที่ลูกค้าแนบ (ตรวจยอดกับบัญชีธนาคารก่อนบันทึก)', 'Customer slips (verify against the bank statement before recording)')) ?></p><div class="slips"><?php foreach ($slips as $s): ?><a class="slip" href="/media/slip/<?= e($s['id']) ?>" target="_blank" rel="noopener"><img src="/media/slip/<?= e($s['id']) ?>" alt=""><span class="xs"><?= e(fmt_date($s['created_at'], true)) ?></span></a><?php endforeach; ?></div></div>
<?php endif; ?>
<div class="grid sm-g2 top">
  <?php if ($o['status'] === 'AWAITING_PAYMENT'): ?>
  <div class="card"><h3><?= e(t('admin.recordPayment')) ?></h3>
    <form method="post" action="<?= e($self) ?>/payment" class="stack-sm mt-m"><?= csrf_field() ?>
      <label class="field"><span><?= e(t('admin.amount')) ?></span><input class="input" name="amount" inputmode="decimal" pattern="\d{1,9}(\.\d{1,2})?" required value="<?= e(number_format(max((int) $o['total_satang'] - $d['paid'], 0) / 100, 2, '.', '')) ?>"></label>
      <label class="field"><span><?= e(t('admin.reference')) ?></span><input class="input" name="reference" required minlength="3" maxlength="200"></label>
      <button class="btn"><?= e(t('admin.recordPayment')) ?></button></form></div>
  <?php endif; ?>
  <?php if ($moves): ?>
  <div class="card"><h3><?= e(t('admin.moveTo')) ?></h3>
    <form method="post" action="/orders/<?= e($o['id']) ?>/transition" class="stack-sm mt-m"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($self) ?>">
      <label class="field"><span><?= e(t('common.status')) ?></span><select class="input" name="to"><?php foreach ($moves as $s): ?><option value="<?= e($s) ?>"><?= e(t('orderStatus.' . $s)) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span><?= e(t('admin.note')) ?></span><input class="input" name="note" maxlength="2000"></label>
      <button class="btn"><?= e(t('admin.apply')) ?></button></form></div>
  <?php endif; ?>
  <div class="card"><h3><?= e(t('orders.history')) ?></h3>
    <ol class="timeline mt-m"><?php foreach ($d['history'] as $h): ?><li><strong><?= e(t('orderStatus.' . $h['to_status'])) ?></strong> <span class="xs muted"><?= e(fmt_date($h['created_at'], true)) ?></span><?= $h['note'] ? '<br><span class="xs">' . e($h['note']) . '</span>' : '' ?></li><?php endforeach; ?></ol></div>
  <div class="card"><h3><?= e(t('orders.paidTotal')) ?></h3>
    <ul class="list-reset small stack-sm mt-m"><?php if (!$d['payments']): ?><li class="muted">—</li><?php endif; ?>
      <?php foreach ($d['payments'] as $p): ?><li class="row between"><span><?= e($p['provider']) ?> · <?= e($p['provider_ref']) ?> · <?= e(fmt_date($p['created_at'], true)) ?></span><span class="tabular"><?= $p['direction'] === 'refund' ? '−' : '' ?><?= e(money((int) $p['amount_satang'])) ?></span></li><?php endforeach; ?></ul></div>
</div>
</div>
