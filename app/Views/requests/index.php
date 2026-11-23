<?php use App\Core\View; \App\Core\View::$title = t('requests.title'); ?>
<?= View::partial('partials/page_head', ['title' => t('requests.title'), 'action' => '<a class="btn" href="/studio">' . e(t('nav.studio')) . '</a>']) ?>
<?php if (!$rows): ?>
  <?= View::partial('partials/empty', ['title' => t('requests.empty'), 'action' => '<a class="btn btn-gold" href="/studio">' . e(t('studio.title')) . '</a>']) ?>
<?php else: ?>
  <ul class="list">
    <?php foreach ($rows as $r): ?>
      <li><a href="/requests/<?= e($r['id']) ?>"><div><strong><?= e($r['title']) ?></strong><br><span class="xs muted"><?= e(fmt_date($r['created_at'])) ?></span></div>
        <div class="row gap-s"><?= View::partial('partials/status', ['status' => $r['status'], 'kind' => 'request']) ?><?= $r['order_status'] ? View::partial('partials/status', ['status' => $r['order_status'], 'kind' => 'order']) : '' ?></div></a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
