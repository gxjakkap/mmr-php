<?php use App\Core\View; use App\Services\{PaymentService, Validate}; View::$title = L('ตั้งค่า', 'Settings');
$labels = ['bank' => L('ธนาคาร', 'Bank'), 'account_no' => L('เลขบัญชี', 'Account number'), 'account_name' => L('ชื่อบัญชี', 'Account name'), 'promptpay' => 'PromptPay', 'note' => L('หมายเหตุถึงลูกค้า', 'Note to customers')]; ?>
<?= View::partial('admin/nav') ?>
<?= View::partial('partials/page_head', ['title' => L('ตั้งค่า', 'Settings')]) ?>
<div class="grid lg-split top">
<form method="post" action="/admin/settings" class="card stack-sm"><?= csrf_field() ?><h3><?= e(L('บัญชีรับโอน', 'Transfer details')) ?></h3>
  <p class="small muted"><?= e(L('แสดงให้ลูกค้าในหน้าคำสั่งซื้อที่รอชำระ ลูกค้าแนบสลิป แล้วทีมงานตรวจกับบัญชีธนาคารก่อนบันทึกยอด (ยังไม่เชื่อมระบบชำระเงินอัตโนมัติ)', 'Shown on orders awaiting payment. Customers attach a slip; staff verify against the bank statement before recording (no payment gateway yet).')) ?></p>
  <?php foreach (PaymentService::FIELDS as $k => $max): ?><label class="field"><span><?= e($labels[$k]) ?></span><input class="input" name="<?= e($k) ?>" maxlength="<?= $max ?>" value="<?= e($pay[$k]) ?>"></label><?php endforeach; ?>
  <button class="btn"><?= e(t('common.save')) ?></button></form>
<div class="card stack-sm"><h3><?= e(L('โปรไฟล์ดีไซเนอร์ (แสดงบนหน้าแรก)', 'Designer profiles (shown on the home page)')) ?></h3>
  <?php if (!$designers): ?><p class="small muted"><?= e(t('admin.noDesigners')) ?></p><?php endif; ?>
  <?php foreach ($designers as $d): $sp = json_list($d['specialties']); ?><form method="post" action="/admin/designers/<?= e($d['id']) ?>" class="stack-sm line"><?= csrf_field() ?>
    <label class="field"><span><?= e(t('admin.publicName')) ?></span><input class="input" name="public_name" required maxlength="80" value="<?= e($d['public_name']) ?>"></label>
    <label class="field"><span>Bio</span><textarea class="input" name="bio" maxlength="400"><?= e($d['bio'] ?? '') ?></textarea></label>
    <fieldset class="chips"><legend class="label"><?= e(L('ความถนัด', 'Specialties')) ?></legend><?php foreach (Validate::SKILLS as $s): ?><label><input type="checkbox" name="specialties[]" value="<?= e($s) ?>"<?= in_array($s, $sp, true) ? ' checked' : '' ?>><span><?= e(t('skill.' . $s)) ?></span></label><?php endforeach; ?></fieldset>
    <button class="btn btn-outline btn-sm"><?= e(t('common.save')) ?></button></form><?php endforeach; ?></div>
</div>
