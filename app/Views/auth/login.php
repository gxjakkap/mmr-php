<?php use App\Core\{Session, View}; $old = Session::takeOld(); \App\Core\View::$title = t('common.signIn');
ob_start(); ?>
<h1 class="mb-m"><?= e(t('auth.loginTitle')) ?></h1>
<form method="post" action="/login" class="stack-sm" novalidate>
  <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
  <label class="field"><span><?= e(t('auth.email')) ?></span><input class="input" type="email" name="email" autocomplete="email" required value="<?= e($old['email'] ?? '') ?>"></label>
  <label class="field"><span><?= e(t('auth.password')) ?></span><input class="input" type="password" name="password" autocomplete="current-password" required minlength="8"></label>
  <button class="btn btn-block"><?= e(t('common.signIn')) ?></button>
</form>
<div class="row between small mt-l">
  <a href="/forgot-password" class="muted"><?= e(t('auth.forgot')) ?></a>
  <a href="/register"><?= e(t('auth.noAccount')) ?> <?= e(t('common.register')) ?></a>
</div>
<?php echo View::partial('auth/shell', ['inner' => ob_get_clean()]);
