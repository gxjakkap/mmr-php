<?php use App\Core\View; \App\Core\View::$title = t('designer.title'); ?>
<?= View::partial('partials/page_head', ['title' => t('designer.title'), 'body' => t('designer.assigned')]) ?>
<?php if (!$rows): ?><?= View::partial('partials/empty', ['title' => t('designer.empty')]) ?><?php else: ?>
<ul class="list"><?php foreach ($rows as $r): ?><li><a href="/designer/requests/<?= e($r['id']) ?>"><strong><?= e($r['title']) ?></strong><?= View::partial('partials/status', ['status' => $r['status'], 'kind' => 'request']) ?></a></li><?php endforeach; ?></ul>
<?php endif; ?>
