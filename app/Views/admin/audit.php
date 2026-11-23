<?php use App\Core\View; \App\Core\View::$title = t('admin.audit'); ?>
<?= View::partial('admin/nav') ?>
<?= View::partial('partials/page_head', ['title' => t('admin.audit')]) ?>
<div class="card scroll-table"><table class="xs">
  <thead><tr><th><?= e(t('common.createdAt')) ?></th><th><?= e(t('admin.action')) ?></th><th>Entity</th><th>Detail</th><th><?= e(t('admin.actor')) ?></th></tr></thead>
  <tbody><?php foreach ($rows as $a): ?>
    <tr><td class="nowrap"><?= e(fmt_date($a['created_at'], true)) ?></td><td><strong><?= e($a['action']) ?></strong></td><td><?= e($a['entity_type']) ?><br><code class="muted"><?= e($a['entity_id']) ?></code></td><td><code><?= e($a['detail']) ?></code></td><td><code class="muted"><?= e($a['actor_id']) ?></code></td></tr>
  <?php endforeach; ?></tbody></table></div>
