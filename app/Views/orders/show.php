<?php use App\Core\View; use App\Domain\Workflow;
$o = $d['order']; \App\Core\View::$title = $o['order_no'];
$idx = array_search($o['status'], Workflow::ORDER_MILESTONES, true);
$moves = Workflow::nextOrder($o['status'], ['customer']); ?>
<div class="stack">
<?= View::partial('partials/page_head', ['eyebrow' => t('orders.number') . ' ' . $o['order_no'], 'title' => (string) $d['title'],
  'action' => View::partial('partials/status', ['status' => $o['status'], 'kind' => 'order'])]) ?>
<?php if ($o['status'] === 'AWAITING_PAYMENT' || $slips || $d['payments']) echo View::partial('partials/paybox', ['o' => $o, 'pay' => $pay, 'slips' => $slips, 'paid' => $d['paid'], 'customer' => true, 'admin' => false]); ?>
<div class="card">
  <h3><?= e(t('orders.progress')) ?></h3>
  <ol class="hsteps mt-m">
    <?php foreach (Workflow::ORDER_MILESTONES as $i => $s): $st = $idx !== false && $i < $idx ? 'done' : ($i === $idx ? 'now' : ''); ?>
      <li class="<?= $st ?>"><i><?= $st === 'done' ? ic('check') : $i + 1 ?></i><span><?= e(t('orderStatus.' . $s)) ?></span></li>
    <?php endforeach; ?>
  </ol>
  <p class="xs muted mt-m"><?= e(t('orders.inspectionNote')) ?></p>
</div>
<div class="grid sm-g2">
  <div class="card"><h3><?= e(t('orders.items')) ?></h3>
    <table class="small tabular mt-m"><tbody>
      <?php foreach ($d['items'] as $i): ?><tr><td><?= e(t('quoteKind.' . $i['kind'])) ?> · <?= e($i['description']) ?></td><td class="right"><?= $i['kind'] === 'discount' ? '−' : '' ?><?= e(money((int) $i['line_total_satang'])) ?></td></tr><?php endforeach; ?>
    </tbody><tfoot>
      <tr class="total-row"><td><?= e(t('requests.total')) ?></td><td class="right"><?= e(money((int) $o['total_satang'])) ?></td></tr>
      <tr><td class="muted"><?= e(t('orders.paidTotal')) ?></td><td class="right"><?= e(money($d['paid'])) ?></td></tr>
    </tfoot></table></div>
  <div class="card"><h3><?= e(t('orders.history')) ?></h3>
    <ol class="timeline mt-m"><?php foreach ($d['history'] as $h): ?><li><strong><?= e(t('orderStatus.' . $h['to_status'])) ?></strong> <span class="xs muted"><?= e(fmt_date($h['created_at'], true)) ?></span></li><?php endforeach; ?></ol></div>
</div>
<?php if ($moves): ?><div class="row"><?= View::partial('partials/transition_buttons', ['moves' => $moves, 'action' => "/orders/{$o['id']}/transition", 'back' => "/orders/{$o['id']}", 'kind' => 'order',
  'labels' => ['COMPLETED' => t('orders.markComplete'), 'REFUND_REVIEW' => t('orders.reportIssue')]]) ?></div><?php endif; ?>
<a class="small" href="/requests/<?= e($o['request_id']) ?>"><?= e(t('common.back')) ?></a>
</div>
