<?php use App\Core\View; View::$title = t('account.title');
$rows = [['hanger', '/wardrobe', t('nav.wardrobe')], ['box', '/requests', L('คำสั่งซื้อของฉัน', 'My orders')], ['sparkle', '/style', 'Outfit Planner'], ['heart', '/saved', L('ที่บันทึกไว้', 'Saved')],
  ['tag', '/market', 'Rewear Marketplace'], ['swap', '/swap', 'Swap'], ['bell', '/inbox', L('การแจ้งเตือน', 'Notifications')], ['book', '/learn', 'Circular Fashion 101'], ['chat', '/contact', L('ติดต่อเรา', 'Contact us')]];
if ($actor->has('designer')) $rows[] = ['studio', '/designer', t('nav.designer')];
if ($actor->isAdmin()) array_push($rows, ['chart', '/admin', t('admin.title')], ['shield', '/admin/moderation', L('ตรวจประกาศ', 'Moderation')], ['gear', '/admin/settings', L('ตั้งค่าระบบ', 'Settings')]); ?>
<div class="w-md stack">
  <div><div class="prof-hero"><img src="<?= e(img('denim')) ?>" alt=""><div class="prof"><span class="avatar xl"><?= e(mb_substr($profile['display_name'] ?: 'M', 0, 1)) ?></span>
    <div><h2><?= e($profile['display_name']) ?></h2><p class="small"><?= e(implode(' · ', array_map(fn($r) => t('role.' . $r), $actor->roles ?: ['customer']))) ?></p></div></div></div>
  <div class="pstats"><?php foreach ([[$stats[0], L('ชิ้นในตู้', 'in wardrobe')], [$stats[1], L('งานที่เสร็จ', 'orders done')], [$stats[2], L('ส่งต่อแล้ว', 'passed on')]] as [$n, $l]): ?><div><b><?= $n ?></b><span class="xs muted"><?= e($l) ?></span></div><?php endforeach; ?></div></div>
  <div class="menu"><?php foreach ($rows as [$i, $h, $l]): ?><a href="<?= e($h) ?>"><?= ic($i) ?><span class="grow"><?= e($l) ?></span><?= ic('arrow') ?></a><?php endforeach; ?></div>
  <details class="card" <?= isset($_GET['edit']) ? 'open' : '' ?>><summary><b><?= e(L('แก้ไขข้อมูลบัญชี', 'Edit account details')) ?></b></summary>
    <form method="post" action="/account" class="stack-sm mt-m">
      <?= csrf_field() ?>
      <label class="field"><span><?= e(t('auth.email')) ?></span><input class="input" value="<?= e($profile['email']) ?>" disabled></label>
      <label class="field"><span><?= e(t('auth.displayName')) ?></span><input class="input" name="display_name" required maxlength="80" value="<?= e($profile['display_name']) ?>"></label>
      <label class="field"><span><?= e(t('account.phone')) ?></span><input class="input" name="phone" inputmode="tel" pattern="[0-9+\- ]{6,20}" value="<?= e($profile['phone'] ?? '') ?>"></label>
      <label class="field"><span><?= e(t('common.language')) ?></span><select class="input" name="locale">
        <option value="th"<?= $profile['locale'] === 'th' ? ' selected' : '' ?>>ไทย</option><option value="en"<?= $profile['locale'] === 'en' ? ' selected' : '' ?>>English</option></select></label>
      <button class="btn"><?= e(t('common.save')) ?></button>
    </form></details>
  <form method="post" action="/logout"><?= csrf_field() ?><button class="btn btn-outline btn-block"><?= e(t('common.signOut')) ?></button></form>
</div>
