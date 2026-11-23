<?php use App\Core\View; use App\Services\Validate; View::$title = 'Rewear'; ?>
<?= photo_banner('Rewear Marketplace', L('ซื้อ-ขายเสื้อผ้ามือสอง ทุกชิ้นเปิดเผยสภาพตามจริงและผ่านการตรวจโดยทีมงาน', 'Pre-loved pieces with honest condition notes, reviewed by our team'), 'denim',
  '<a class="btn btn-gold mt-m" href="/wardrobe">' . ic('tag') . ' ' . e(L('ลงขายจากตู้ของฉัน', 'Sell from my wardrobe')) . '</a>') ?>
<form class="tools" method="get" action="/market" role="search">
  <label class="search"><?= ic('search') ?><input name="q" value="<?= e($q) ?>" placeholder="<?= e(L('ค้นหาสินค้า', 'Search items')) ?>" aria-label="<?= e(L('ค้นหาสินค้า', 'Search items')) ?>"></label>
  <select class="input sel-auto" name="category" aria-label="<?= e(t('wardrobe.category')) ?>"><option value=""><?= e(t('wardrobe.category')) ?>: <?= e(t('common.all')) ?></option>
    <?php foreach (Validate::CATEGORIES as $c): ?><option value="<?= e($c) ?>"<?= $c === $category ? ' selected' : '' ?>><?= e(t('category.' . $c)) ?></option><?php endforeach; ?></select>
  <button class="btn btn-outline"><?= e(L('ค้นหา', 'Search')) ?></button></form>
<?php if ($mine): ?><section class="sec"><?= section_head(L('ประกาศของฉัน', 'My listings')) ?><div class="olist">
  <?php foreach ($mine as $l): ?><a class="ocard" href="/market/<?= e($l['id']) ?>"><?= photo($l['cover_image_id'], $l['category'], 'mini') ?><div class="grow"><strong><?= e($l['title']) ?></strong><p class="xs muted tabular"><?= e(money((int) $l['price_satang'])) ?></p></div>
    <span class="badge <?= $l['status'] === 'active' ? 'tone-sage' : ($l['status'] === 'rejected' ? 'tone-danger' : 'tone-gold') ?>"><?= e(listing_status_label($l['status'])) ?></span></a><?php endforeach; ?></div></section><?php endif; ?>
<section class="sec"><?= section_head(L('สินค้าทั้งหมด', 'All items') . ' (' . count($rows) . ')') ?>
<?php if ($rows): ?><div class="mgrid"><?php foreach ($rows as $l) echo View::partial('partials/market_card', ['l' => $l, 'favs' => $favs, 'back' => '/market']); ?></div>
<?php else: ?><div class="empty"><img class="empty-img" src="<?= e(img('jacket')) ?>" alt=""><p class="title"><?= e(L('ยังไม่มีสินค้าในตลาด', 'No items yet')) ?></p><p class="small muted mt-s"><?= e(L('เป็นคนแรกที่ลงขายเสื้อผ้าที่ไม่ได้ใส่แล้ว', 'Be the first to list something you no longer wear')) ?></p><a class="btn btn-gold mt-l" href="/wardrobe"><?= ic('tag') ?> <?= e(L('ลงขาย', 'Sell')) ?></a></div><?php endif; ?></section>
<p class="alert xs mt-l"><?= e(L('ตลาดนี้ยังไม่มีระบบชำระเงินในตัว ผู้ซื้อและผู้ขายตกลงการชำระและจัดส่งกันเองหลังผู้ขายตอบรับ ค่าส่งไม่รวมในราคา MEMORii ไม่ได้เป็นคู่สัญญาในการซื้อขายระหว่างสมาชิก', 'No built-in payments yet: buyer and seller arrange payment and delivery after the seller accepts. Shipping is not included. MEMORii is not a party to sales between members.')) ?></p>
