<?php use App\Core\View; use App\Domain\Workflow;
$r = $d['request']; \App\Core\View::$title = $r['title']; $self = '/admin/requests/' . $r['id'];
$moves = Workflow::nextRequest($r['status'], ['admin']);
$q = $d['quotations'][0] ?? null; $v = $q ? (array_values(array_filter($d['versions'], fn($x) => $x['id'] === $q['design_version_id']))[0] ?? null) : null; ?>
<?= View::partial('admin/nav') ?>
<div class="stack">
<?= View::partial('partials/page_head', ['eyebrow' => t('admin.requests'), 'title' => $r['title']]) ?>
<?php if ($d['order']): ?><p class="alert tone-sage"><a href="/admin/orders/<?= e($d['order']['id']) ?>"><?= e($d['order']['order_no']) ?></a></p><?php endif; ?>
<div class="grid lg-split top">
  <div class="stack">
    <?= View::partial('partials/request/summary', ['d' => $d]) ?>
    <?php if ($moves): ?>
    <div class="card"><h3><?= e(t('admin.moveTo')) ?></h3>
      <form method="post" action="/requests/<?= e($r['id']) ?>/transition" class="grid sm-g3 mt-m">
        <?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($self) ?>">
        <label class="field"><span><?= e(t('common.status')) ?></span><select class="input" name="to"><?php foreach ($moves as $m): ?><option value="<?= e($m) ?>"><?= e(t('reqStatus.' . $m)) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span><?= e(t('admin.note')) ?></span><input class="input" name="note" maxlength="2000"></label>
        <div class="row"><button class="btn"><?= e(t('admin.apply')) ?></button></div>
      </form></div>
    <?php endif; ?>
    <?php if (in_array($r['status'], ['UNDER_REVIEW', 'FEASIBILITY_REVIEW', 'QUOTE_PREPARING', 'QUOTE_REJECTED'], true)): ?>
    <div class="card"><h3><?= e(t('admin.assignDesigner')) ?></h3>
      <?php if (!$designers): ?><p class="small muted mt-s"><?= e(t('admin.noDesigners')) ?></p><?php else: ?>
      <form method="post" action="<?= e($self) ?>/assign" class="row mt-m">
        <?= csrf_field() ?>
        <label class="field flex-1"><span><?= e(t('admin.selectDesigner')) ?></span><select class="input" name="designer_id" required>
          <option value="">—</option>
          <?php foreach ($designers as $x): ?><option value="<?= e($x['id']) ?>"<?= $r['assigned_designer_id'] === $x['id'] ? ' selected' : '' ?>><?= e($x['public_name']) ?></option><?php endforeach; ?></select></label>
        <button class="btn"><?= e(t('admin.assignDesigner')) ?></button>
      </form><?php endif; ?></div>
    <?php endif; ?>
    <?php if ($q): ?><div class="card"><h3><?= e(t('requests.quotation')) ?></h3><div class="mt-m"><?= View::partial('partials/request/quotation', ['q' => $q, 'v' => $v]) ?></div></div><?php endif; ?>
  </div>
  <div class="stack">
    <?= View::partial('partials/request/messages', ['d' => $d, 'viewerId' => $actor->id, 'back' => $self, 'allowInternal' => true]) ?>
    <?= View::partial('partials/request/timeline', ['d' => $d]) ?>
  </div>
</div>
</div>
