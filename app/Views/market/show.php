<?php use App\Core\View; $l = $d['listing']; View::$title = $l['title']; $id = e($l['id']); $mo = $d['myOffer']; ?>
<div class="w-md-l">
<a class="btn btn-ghost btn-sm mb-m" href="/market"><?= ic('back') ?> Rewear</a>
<div class="detail">
  <div><?php if ($d['images']): ?><div class="ph hero-ph"><img src="/media/garment/<?= e($d['images'][0]['id']) ?>" alt="<?= e($l['title']) ?>"></div>
    <div class="thumbs"><?php foreach (array_slice($d['images'], 1, 6) as $img): ?><span class="th"><img src="/media/garment/<?= e($img['id']) ?>" alt=""></span><?php endforeach; ?></div>
    <?php else: ?><?= photo(null, $l['category'], 'hero-ph') ?><?php endif; ?></div>
  <div class="stack">
    <div class="row between"><h1 class="gname"><?= e($l['title']) ?></h1><span class="inline-fav"><?= fav_button('m:' . $l['id'], in_array('m:' . $l['id'], $favs, true), '/market/' . $l['id']) ?></span></div>
    <p class="price big tabular"><?= e(money((int) $l['price_satang'])) ?></p>
    <div class="row"><span class="badge <?= $l['status'] === 'active' ? 'tone-sage' : 'tone-gold' ?>"><?= e(listing_status_label($l['status'])) ?></span><?= cond_badge($l['condition']) ?><?php if ($l['size_label']): ?><span class="badge"><?= e($l['size_label']) ?></span><?php endif; ?><span class="badge"><?= e(t('category.' . $l['category'])) ?></span></div>
    <div class="card"><p class="lbl"><?= e(L('สภาพสินค้า (ผู้ขายเปิดเผย)', 'Condition (disclosed by seller)')) ?></p><p class="small pre"><?= e($l['condition_disclosure']) ?></p></div>
    <p class="small muted"><?= e(L('ผู้ขาย', 'Seller')) ?>: <?= e($d['isSeller'] ? L('คุณ', 'You') : $l['seller_name']) ?></p>
    <?php if ($l['moderation_note'] && ($d['isSeller'] || $actor->isAdmin())): ?><p class="alert xs"><?= e(L('หมายเหตุจากทีมงาน', 'Note from our team')) ?>: <?= e($l['moderation_note']) ?></p><?php endif; ?>

    <?php if (!$d['isSeller'] && $l['status'] === 'active' && (!$mo || $mo['status'] === 'declined')): ?>
      <form method="post" action="/market/<?= $id ?>/buy" class="card stack-sm"><?= csrf_field() ?><p class="lbl"><?= e(L('ขอซื้อชิ้นนี้', 'Request to buy')) ?></p>
        <textarea class="input" name="message" maxlength="500" placeholder="<?= e(L('ข้อความถึงผู้ขาย เช่น วิธีรับของที่สะดวก', 'Message to the seller, e.g. how you’d like to receive it')) ?>" aria-label="<?= e(L('ข้อความ', 'Message')) ?>"></textarea>
        <button class="btn btn-lg"><?= ic('tag') ?> <?= e(L('ส่งคำขอซื้อ', 'Send request')) ?> · <?= e(money((int) $l['price_satang'])) ?></button></form>
    <?php endif; ?>
    <?php if ($mo && $mo['status'] !== 'declined'): ?>
      <div class="alert <?= $mo['status'] === 'accepted' || $mo['status'] === 'completed' ? 'tone-sage' : '' ?>">
        <?= e(match ($mo['status']) { 'accepted' => L('ผู้ขายตอบรับคำขอของคุณแล้ว — ตกลงการชำระและจัดส่งกับผู้ขายผ่านช่องทางที่ปลอดภัย', 'The seller accepted — arrange payment and delivery with them safely'),
          'completed' => L('การซื้อขายเสร็จสิ้น', 'Sale completed'), default => L('ส่งคำขอซื้อแล้ว รอผู้ขายตอบ', 'Request sent — waiting for the seller') }) ?>
        <?php if ($mo['status'] === 'requested'): ?><form method="post" action="/market-offers/<?= e($mo['id']) ?>/cancel" class="mt-s"><?= csrf_field() ?><button class="btn btn-outline btn-sm"><?= e(L('ยกเลิกคำขอ', 'Cancel request')) ?></button></form><?php endif; ?></div>
    <?php elseif ($mo): ?><p class="alert tone-danger"><?= e(L('ผู้ขายปฏิเสธคำขอก่อนหน้า', 'The seller declined your previous request')) ?></p><?php endif; ?>

    <?php if ($d['isSeller']): ?><div class="card stack-sm"><p class="lbl"><?= e(L('คำขอซื้อ', 'Purchase requests')) ?> (<?= count($d['offers']) ?>)</p>
      <?php foreach ($d['offers'] as $o): ?><div class="line"><b><?= e($o['buyer_name'] ?: L('สมาชิก', 'Member')) ?></b> <span class="xs muted"><?= e(fmt_date($o['created_at'], true)) ?></span>
        <?php if ($o['message']): ?><p class="small"><?= e($o['message']) ?></p><?php endif; ?>
        <?php if ($o['status'] === 'requested' && $l['status'] === 'active'): ?><div class="row mt-s">
          <form method="post" action="/market-offers/<?= e($o['id']) ?>/decide"><?= csrf_field() ?><input type="hidden" name="decision" value="accept"><button class="btn btn-sage btn-sm"><?= e(L('ตอบรับ', 'Accept')) ?></button></form>
          <form method="post" action="/market-offers/<?= e($o['id']) ?>/decide"><?= csrf_field() ?><input type="hidden" name="decision" value="decline"><button class="btn btn-outline btn-sm"><?= e(L('ปฏิเสธ', 'Decline')) ?></button></form></div>
        <?php else: ?><span class="badge"><?= e(['accepted' => L('ตอบรับแล้ว', 'Accepted'), 'declined' => L('ปฏิเสธแล้ว', 'Declined'), 'cancelled' => L('ยกเลิก', 'Cancelled'), 'completed' => L('ขายแล้ว', 'Sold'), 'requested' => L('รอตอบ', 'Pending')][$o['status']] ?? $o['status']) ?></span><?php endif; ?></div>
      <?php endforeach; ?><?php if (!$d['offers']): ?><p class="small muted">—</p><?php endif; ?>
      <div class="row">
        <?php if ($l['status'] === 'reserved'): ?><form method="post" action="/market/<?= $id ?>/status"><?= csrf_field() ?><input type="hidden" name="to" value="sold"><button class="btn btn-sm"><?= e(L('ทำเครื่องหมายว่าขายแล้ว', 'Mark as sold')) ?></button></form><?php endif; ?>
        <?php if (in_array($l['status'], ['pending_review', 'active', 'reserved'], true)): ?><form method="post" action="/market/<?= $id ?>/status"><?= csrf_field() ?><input type="hidden" name="to" value="withdrawn"><button class="btn btn-outline btn-sm"><?= e(L('ถอนประกาศ', 'Withdraw')) ?></button></form><?php endif; ?>
      </div></div><?php endif; ?>

    <?php if ($actor->isAdmin() && $l['status'] === 'pending_review'): ?><?= View::partial('partials/moderate_form', ['kind' => 'market', 'id' => $l['id'], 'back' => '/market/' . $l['id']]) ?><?php endif; ?>
    <p class="xs muted"><?= e(L('ความปลอดภัย: นัดรับในที่สาธารณะหรือใช้บริการขนส่งที่ติดตามพัสดุได้ และอย่าโอนเงินก่อนได้รับข้อมูลที่ยืนยันได้', 'Safety: meet in public or use tracked shipping, and don’t pay before you can verify details.')) ?></p>
  </div></div></div>
