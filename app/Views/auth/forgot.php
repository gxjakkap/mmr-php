<?php use App\Core\View; \App\Core\View::$title = t('auth.forgotTitle'); ob_start(); ?>
<h1 class="mb-m"><?= e(t('auth.forgotTitle')) ?></h1>
<form method="post" action="/forgot-password" class="stack-sm">
  <?= csrf_field() ?>
  <label class="field"><span><?= e(t('auth.email')) ?></span><input class="input" type="email" name="email" required autocomplete="email"></label>
  <button class="btn btn-block"><?= e(t('auth.sendReset')) ?></button>
</form>
<?php echo View::partial('auth/shell', ['inner' => ob_get_clean()]);
