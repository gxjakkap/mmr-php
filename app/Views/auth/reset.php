<?php use App\Core\View; \App\Core\View::$title = t('auth.updatePassword'); ob_start(); ?>
<h1 class="mb-m"><?= e(t('auth.updatePassword')) ?></h1>
<form method="post" action="/reset-password" class="stack-sm">
  <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
  <label class="field"><span><?= e(t('auth.newPassword')) ?></span><input class="input" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><span class="hint"><?= e(t('auth.passwordHint')) ?></span></label>
  <button class="btn btn-block"><?= e(t('auth.updatePassword')) ?></button>
</form>
<?php echo View::partial('auth/shell', ['inner' => ob_get_clean()]);
