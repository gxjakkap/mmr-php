<?php use App\Core\View; use App\Domain\Workflow; \App\Core\View::$title = t('admin.requests'); ?>
<?= View::partial('admin/nav') ?>
<?= View::partial('partials/page_head', ['title' => t('admin.requests')]) ?>
<nav class="scroll-x mb-m" aria-label="<?= e(t('admin.filter')) ?>">
  <a class="btn btn-chip"<?= !$status ? ' aria-current="page"' : '' ?> href="/admin/requests"><?= e(t('common.all')) ?></a>
  <?php foreach (array_diff(Workflow::REQUEST_STATUSES, ['DRAFT']) as $s): ?><a class="btn btn-chip"<?= $status === $s ? ' aria-current="page"' : '' ?> href="<?= e(url('/admin/requests', ['status' => $s])) ?>"><?= e(t('reqStatus.' . $s)) ?></a><?php endforeach; ?>
</nav>
<?php if (!$rows): ?><?= View::partial('partials/empty', ['title' => t('admin.noRequests')]) ?><?php else: ?>
<ul class="list"><?php foreach ($rows as $r): ?>
  <li><a href="/admin/requests/<?= e($r['id']) ?>"><div><strong><?= e($r['title']) ?></strong><br>
    <span class="xs muted"><?= e($r['customer_name'] ?: t('admin.customer')) ?> · <?= e(fmt_date($r['submitted_at'])) ?><?= $r['designer_name'] ? ' · ' . e($r['designer_name']) : '' ?></span></div>
    <?= View::partial('partials/status', ['status' => $r['status'], 'kind' => 'request']) ?></a></li>
<?php endforeach; ?></ul><?php endif; ?>
