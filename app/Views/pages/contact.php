<?php use App\Core\{Env, View}; View::$title = L('ติดต่อเรา', 'Contact');
$email = Env::get('CONTACT_EMAIL', ''); $line = Env::get('CONTACT_LINE', ''); $phone = Env::get('CONTACT_PHONE', ''); ?>
<div class="w-md stack"><h1><?= e(L('ติดต่อเรา', 'Contact us')) ?></h1>
<p class="muted"><?= e(L('สอบถามเรื่องคำสั่งซื้อ การชำระเงิน ปัญหาการซื้อขายหรือแลกเปลี่ยน หรือขอใช้สิทธิ์เกี่ยวกับข้อมูลส่วนบุคคล', 'Questions about orders, payments, a Rewear or Swap problem, or a personal-data request')) ?></p>
<div class="menu">
  <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= ic('chat') ?><span class="grow"><?= e(L('อีเมล', 'Email')) ?>: <?= e($email) ?></span><?= ic('arrow') ?></a><?php endif; ?>
  <?php if ($line): ?><span class="item-like"><?= ic('chat') ?><span class="grow">LINE: <?= e($line) ?></span></span><?php endif; ?>
  <?php if ($phone): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= ic('chat') ?><span class="grow"><?= e(L('โทร', 'Phone')) ?>: <?= e($phone) ?></span><?= ic('arrow') ?></a><?php endif; ?>
  <?php if (!$email && !$line && !$phone): ?><span class="item-like"><span class="grow muted"><?= e(L('ยังไม่ได้ตั้งค่าช่องทางติดต่อ', 'Contact details have not been configured yet')) ?></span></span><?php endif; ?>
</div>
<?php if ($actor): ?><p class="small"><?= e(L('เรื่องคำขอออกแบบหรือคำสั่งซื้อ ส่งข้อความในหน้าคำขอนั้นได้เลย ทีมงานจะเห็นทันที', 'For a design request or order, message us on that request’s page — the team sees it there.')) ?> <a href="/requests"><?= e(L('ไปที่คำสั่งซื้อ', 'Go to orders')) ?></a></p><?php endif; ?>
</div>
