<?php
/** @var string $content @var ?App\Core\Actor $actor */
use App\Core\{I18n, Session, View};
$locale = I18n::locale();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$is = fn(string $href) => $href === '/' ? $path === '/' : str_starts_with($path, $href);
$cur = fn(string $href) => $is($href) ? ' aria-current="page"' : '';
$nav = [['/', t('nav.home')], ['/studio', t('nav.studio')], ['/market', 'Rewear'], ['/swap', 'Swap'], ['/learn', L('เรียนรู้', 'Learn')]];
if ($actor) {
    $nav = [['/', t('nav.home')], ['/wardrobe', t('nav.wardrobe')], ['/studio', t('nav.studio')], ['/market', 'Rewear'], ['/swap', 'Swap'], ['/requests', L('คำสั่งซื้อ', 'Orders')]];
    if ($actor->has('designer')) $nav[] = ['/designer', t('nav.designer')];
    if ($actor->isAdmin()) $nav[] = ['/admin', t('nav.admin')];
}
$unseen = $actor ? (new App\Services\NotificationService(App\Core\Db::get()))->unseen($actor) : 0;
$flash = Session::takeFlash();
$pageTitle = View::$title ?? ($title ?? null);
?><!doctype html>
<html lang="<?= e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#10263F">
  <title><?= e($pageTitle ? $pageTitle . ' · MEMORii' : 'MEMORii — ' . L('เสื้อผ้าเดิม สู่ดีไซน์ใหม่', 'Old clothes, new designs')) ?></title>
  <meta name="description" content="<?= e(L('เปลี่ยนเสื้อผ้าที่ไม่ได้ใส่ เป็นเสื้อผ้าตัวใหม่ กระเป๋า แอคเซสซอรี่ หรือของแต่งบ้าน ซื้อขายมือสอง และแลกเปลี่ยนเสื้อผ้า', 'Turn clothes you no longer wear into new clothing, bags, accessories or home pieces — or rewear and swap them.')) ?>">
  <meta property="og:title" content="MEMORii">
  <meta property="og:image" content="/assets/img/hero.jpg">
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="/assets/app.css?v=3">
  <script src="/assets/app.js?v=3" defer></script>
</head>
<body class="<?= e(View::$bodyClass) ?>">
<a class="skip" href="#main"><?= e(t('common.skipToContent')) ?></a>
<header class="site-header">
  <div class="container">
    <a class="logo" href="/" aria-label="MEMORii">MEMOR<span>ii</span></a>
    <nav class="nav-desktop" aria-label="primary">
      <?php foreach ($nav as [$href, $label]): ?><a href="<?= e($href) ?>"<?= $cur($href) ?>><?= e($label) ?></a><?php endforeach; ?>
    </nav>
    <div class="bar-r">
      <?php if ($actor): ?>
        <a class="iconbtn" href="/inbox" aria-label="<?= e(L('การแจ้งเตือน', 'Notifications') . ' ' . $unseen) ?>"><?= ic('bell') ?><?php if ($unseen): ?><span class="dot"><?= $unseen > 9 ? '9+' : $unseen ?></span><?php endif; ?></a>
      <?php endif; ?>
      <form class="inline hide-m" method="post" action="/locale">
        <?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
        <input type="hidden" name="locale" value="<?= $locale === 'th' ? 'en' : 'th' ?>">
        <button class="btn btn-chip" aria-label="<?= e(t('common.language')) ?>"><?= $locale === 'th' ? 'EN' : 'ไทย' ?></button>
      </form>
      <?php if ($actor): ?>
        <a class="avatar hide-m" href="/account" aria-label="<?= e(t('nav.account')) ?>"><?= e(mb_substr($actor->displayName ?: 'M', 0, 1)) ?></a>
      <?php else: ?>
        <a class="btn btn-sm btn-gold" href="/login"><?= e(t('common.signIn')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main id="main" class="<?= View::$full ? 'full' : 'container' ?>">
  <?php if ($flash): ?><div class="<?= View::$full ? 'wrap ' : '' ?>stack-sm mb-m flash"><?php foreach ($flash as [$type, $msg]): ?>
    <div class="alert <?= $type === 'error' ? 'tone-danger' : 'tone-sage' ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= e($msg) ?></div>
  <?php endforeach; ?></div><?php endif; ?>
  <?= $content ?>
</main>
<footer class="site-footer"><div class="container row between">
  <span>© <?= date('Y') ?> MEMORii · <?= e(t('common.tagline')) ?></span>
  <span class="row gap-s"><a href="/learn"><?= e(L('เรียนรู้', 'Learn')) ?></a> · <a href="/privacy"><?= e(L('นโยบายความเป็นส่วนตัว', 'Privacy')) ?></a> · <a href="/terms"><?= e(L('ข้อกำหนดการใช้งาน', 'Terms')) ?></a> · <a href="/contact"><?= e(L('ติดต่อเรา', 'Contact')) ?></a></span>
</div></footer>
<?php $tabs = $actor
  ? [['/', 'home', t('nav.home')], ['/studio', 'studio', t('nav.studio')], ['/wardrobe', 'hanger', t('nav.wardrobe')], ['/requests', 'box', L('คำสั่งซื้อ', 'Orders')], ['/account', 'user', t('nav.account')]]
  : [['/', 'home', t('nav.home')], ['/studio', 'studio', t('nav.studio')], ['/market', 'tag', 'Rewear'], ['/learn', 'book', L('เรียนรู้', 'Learn')], ['/login', 'user', t('common.signIn')]]; ?>
<nav class="bottom-nav" aria-label="mobile"><ul>
  <?php foreach ($tabs as [$href, $icon, $label]): ?>
    <li><a href="<?= e($href) ?>"<?= $cur($href) ?>><?= ic($icon) ?><span><?= e($label) ?></span></a></li>
  <?php endforeach; ?>
</ul></nav>
</body>
</html>
