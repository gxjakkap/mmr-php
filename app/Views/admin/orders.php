<?php use App\Core\View; use App\Domain\Workflow; \App\Core\View::$title = t('admin.orders'); ?>
<?= View::partial('admin/nav') ?>
<?= View::partial('partials/page_head', ['title' => t('admin.orders')]) ?>
<nav class="scroll-x mb-m"><a class="btn btn-chip"<?= !$status ? ' aria-current="page"' : '' ?> href="/admin/orders"><?= e(t('common.all')) ?></a>
  <?php foreach (Workflow::ORDER_STATUSES as $s): ?><a class="btn btn-chip"<?= $status === $s ? ' aria-current="page"' : '' ?> href="<?= e(url('/admin/orders', ['status' => $s])) ?>"><?= e(t('orderStatus.' . $s)) ?></a><?php endforeach; ?></nav>
<?php if (!$rows): ?><?= View::partial('partials/empty', ['title' => t('admin.noOrders')]) ?><?php else: ?>
<ul class="list"><?php foreach ($rows as $o): ?>
  <li><a href="/admin/orders/<?= e($o['id']) ?>"><div><strong><?= e($o['order_no']) ?> · <?= e($o['title']) ?></strong><br><span class="xs muted"><?= e(money((int) $o['total_satang'])) ?></span></div>
    <?= View::partial('partials/status', ['status' => $o['status'], 'kind' => 'order']) ?></a></li>
<?php endforeach; ?></ul><?php endif; ?>
