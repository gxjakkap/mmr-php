<?php /** @var string $cur */ ?>
<aside class="side"><div class="side-card"><h2>My Wardrobe</h2><p class="xs muted"><?= e(L('ตู้เสื้อผ้าของฉัน', 'Your digital closet')) ?></p><nav>
<?php foreach ([['wardrobe', 'hanger', '/wardrobe', L('เสื้อผ้าของฉัน', 'My garments')], ['studio', 'studio', '/studio', L('ออกแบบใหม่ (Creative Studio)', 'Creative Studio')],
  ['style', 'sparkle', '/style', L('จัดชุด (Outfit Planner)', 'Outfit Planner')], ['saved', 'heart', '/saved', L('ที่บันทึกไว้', 'Saved')], ['requests', 'clock', '/requests', L('คำสั่งซื้อและกิจกรรม', 'Orders & activity')],
  ['market', 'tag', '/market', L('ตลาดมือสอง', 'Rewear')], ['swap', 'swap', '/swap', L('แลกเปลี่ยน', 'Swap')], ['account', 'user', '/account', L('ตั้งค่าบัญชี', 'Account')]] as [$k, $i, $h, $l]): ?>
  <a href="<?= e($h) ?>"<?= $k === $cur ? ' aria-current="page"' : '' ?>><?= ic($i) ?><span><?= e($l) ?></span></a>
<?php endforeach; ?></nav></div>
<div class="side-promo"><img src="<?= e(img('crossbody')) ?>" alt=""><p><b><?= e(L('ไอเดียสัปดาห์นี้', 'Idea of the week')) ?></b><br><span class="xs"><?= e(L('ยีนส์ตัวเก่า → กระเป๋าสะพาย', 'Old jeans → crossbody bag')) ?></span></p></div></aside>
