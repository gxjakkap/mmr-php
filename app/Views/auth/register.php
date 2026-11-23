<?php use App\Core\{Session, View}; $old = Session::takeOld(); \App\Core\View::$title = t('common.register');
ob_start(); ?>
<h1 class="mb-m"><?= e(t('auth.registerTitle')) ?></h1>
<form method="post" action="/register" class="stack-sm">
  <?= csrf_field() ?>
  <label class="field"><span><?= e(t('auth.displayName')) ?></span><input class="input" name="display_name" required maxlength="80" autocomplete="name" value="<?= e($old['display_name'] ?? '') ?>"></label>
  <label class="field"><span><?= e(t('auth.email')) ?></span><input class="input" type="email" name="email" required autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></label>
  <label class="field"><span><?= e(t('auth.password')) ?></span><input class="input" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><span class="hint"><?= e(t('auth.passwordHint')) ?></span></label>
  <label class="check small"><input type="checkbox" name="accept_terms" value="1" required> <span><?= e(L('ฉันยอมรับ', 'I accept the')) ?> <a href="/terms" target="_blank"><?= e(L('ข้อกำหนดการใช้งาน', 'Terms of use')) ?></a> <?= e(L('และรับทราบ', 'and have read the')) ?> <a href="/privacy" target="_blank"><?= e(L('นโยบายความเป็นส่วนตัว', 'Privacy policy')) ?></a></span></label>
  <button class="btn btn-block"><?= e(t('common.register')) ?></button>
</form>
<p class="small mt-l"><?= e(t('auth.haveAccount')) ?> <a href="/login"><?= e(t('common.signIn')) ?></a></p>
<?php echo View::partial('auth/shell', ['inner' => ob_get_clean()]);
