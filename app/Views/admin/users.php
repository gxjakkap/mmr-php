<?php use App\Core\View; \App\Core\View::$title = t('admin.users'); ?>
<?= View::partial('admin/nav') ?>
<div class="stack">
<?= View::partial('partials/page_head', ['title' => t('admin.users')]) ?>
<div class="card"><h3><?= e(t('admin.grantRole')) ?></h3>
  <form method="post" action="/admin/users/grant" class="grid sm-g4 mt-m"><?= csrf_field() ?>
    <label class="field"><span><?= e(t('admin.userId')) ?></span><input class="input" name="user_id" required pattern="[0-9a-fA-F\-]{36}"></label>
    <label class="field"><span><?= e(t('admin.role')) ?></span><select class="input" name="role"><?php foreach (['designer', 'tailor', 'qc', 'support', 'admin'] as $r): ?><option value="<?= e($r) ?>"><?= e(t('role.' . $r)) ?></option><?php endforeach; ?></select></label>
    <label class="field"><span><?= e(t('admin.publicName')) ?></span><input class="input" name="public_name" maxlength="80"></label>
    <div class="row"><button class="btn"><?= e(t('admin.grantRole')) ?></button></div>
  </form></div>
<ul class="list"><?php foreach ($users as $u): ?>
  <li class="item"><div><strong><?= e($u['display_name'] ?: '—') ?></strong> <span class="xs muted"><?= e($u['email']) ?><?= $u['email_verified_at'] ? '' : ' · unverified' ?></span><br><code class="muted"><?= e($u['id']) ?></code></div>
    <div class="row gap-s"><?php foreach ($u['roles'] as $r): ?><span class="badge"><?= e(t('role.' . $r)) ?></span><?php endforeach; ?></div></li>
<?php endforeach; ?></ul>
</div>
