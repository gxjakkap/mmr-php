<?php use App\Core\{I18n, View}; View::$title = t('admin.title');
$tiles = [['newRequests', '/admin/requests?status=SUBMITTED', 'box'], ['inReview', '/admin/requests?status=UNDER_REVIEW', 'clock'], ['awaitingQuote', '/admin/requests?status=QUOTE_PREPARING', 'studio'],
  ['quoteSent', '/admin/requests?status=QUOTE_SENT', 'chat'], ['awaitingPayment', '/admin/orders?status=AWAITING_PAYMENT', 'card'], ['activeOrders', '/admin/orders', 'truck'], ['garments', '/admin', 'hanger'], ['customers', '/admin/users', 'user']];
$extra = [[L('ประกาศรอตรวจ', 'Listings to review'), $m['listingsPending'], '/admin/moderation', 'shield'], [L('ออเดอร์ที่มีสลิปรอตรวจ', 'Orders with slips to check'), $m['slipsWaiting'], '/admin/orders?status=AWAITING_PAYMENT', 'upload']];
$max = max(1, ...array_map(fn($r) => (int) $r['n'], $m['popular'] ?: [['n' => 1]])); ?>
<?= View::partial('admin/nav') ?>
<?= View::partial('partials/page_head', ['title' => L('ภาพรวมธุรกิจ', 'Business overview'), 'body' => t('admin.metricsNote')]) ?>
<ul class="grid g2 lg-g4 list-reset">
  <?php foreach ($tiles as [$k, $href, $i]): ?><li><a class="card kpi" href="<?= e($href) ?>"><span class="pi"><?= ic($i) ?></span><p class="xs muted mt-s"><?= e(t('admin.' . $k)) ?></p><p class="metric"><?= (int) $m[$k] ?></p></a></li><?php endforeach; ?>
  <?php foreach ($extra as [$l, $v, $href, $i]): ?><li><a class="card kpi" href="<?= e($href) ?>"><span class="pi"><?= ic($i) ?></span><p class="xs muted mt-s"><?= e($l) ?></p><p class="metric"><?= (int) $v ?></p></a></li><?php endforeach; ?>
  <li><div class="card kpi"><span class="pi"><?= ic('card') ?></span><p class="xs muted mt-s"><?= e(L('รับชำระแล้ว (บันทึกโดยทีมงาน)', 'Payments recorded')) ?></p><p class="metric tabular"><?= e(money($m['paidTotal'])) ?></p></div></li>
</ul>
<div class="card mt-l"><h3><?= e(L('บริการที่ถูกขอมากที่สุด', 'Most requested services')) ?></h3>
<?php if ($m['popular']): ?><div class="bars mt-m"><?php foreach ($m['popular'] as $r): $name = $r['name_th'] === '' ? t('studio.modeCustom') : (I18n::locale() === 'th' ? $r['name_th'] : $r['name_en']); ?>
  <div class="barrow"><span class="small"><?= e($name) ?></span><div class="b"><i class="<?= pct_class($r['n'] / $max * 100) ?>"></i></div><span class="small tabular"><?= (int) $r['n'] ?></span></div><?php endforeach; ?></div>
<?php else: ?><p class="small muted mt-s">—</p><?php endif; ?></div>
