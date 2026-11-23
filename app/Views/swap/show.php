<?php use App\Core\View; $l = $d['listing']; View::$title = $l['title']; $mine = $d['mine'];
$act = fn(string $rid, string $do, string $label, string $cls = 'btn-sm') => '<form method="post" action="/swap-requests/' . e($rid) . '"> ' . csrf_field() . '<input type="hidden" name="do" value="' . e($do) . '"><button class="btn ' . $cls . '">' . e($label) . '</button></form>';
$rs = fn($s) => ['proposed' => L('รอเจ้าของตอบ', 'Waiting for owner'), 'accepted_by_owner' => L('เจ้าของตอบรับ — รอผู้เสนอยืนยัน', 'Owner accepted — awaiting confirmation'),
  'mutually_accepted' => L('ยืนยันทั้งสองฝ่าย — ส่งของได้', 'Both confirmed — ship your items'), 'completed' => L('แลกสำเร็จ', 'Completed'), 'declined' => L('ไม่ได้รับเลือก', 'Not selected'), 'cancelled' => L('ยกเลิก', 'Cancelled')][$s] ?? $s; ?>
<div class="w-md-l">
<a class="btn btn-ghost btn-sm mb-m" href="/swap"><?= ic('back') ?> Swap</a>
<div class="detail">
  <div><?php if ($d['images']): ?><div class="ph hero-ph"><img src="/media/garment/<?= e($d['images'][0]['id']) ?>" alt="<?= e($l['title']) ?>"></div><?php else: ?><?= photo(null, $l['category'], 'hero-ph') ?><?php endif; ?></div>
  <div class="stack">
    <h1 class="gname"><?= e($l['title']) ?></h1>
    <div class="row"><span class="badge"><?= e(listing_status_label($l['status'])) ?></span><?= cond_badge($l['condition']) ?></div>
    <div class="card"><p class="lbl"><?= e(L('อยากแลกกับ', 'Looking for')) ?></p><p><?= e($l['looking_for'] ?: '—') ?></p><p class="lbl mt-m"><?= e(L('สภาพ', 'Condition')) ?></p><p class="small pre"><?= e($l['condition_disclosure']) ?></p></div>
    <p class="small muted"><?= e(L('เจ้าของ', 'Owner')) ?>: <?= e($d['isOwner'] ? L('คุณ', 'You') : $l['owner_name']) ?></p>
    <?php if (!$d['isOwner'] && $l['status'] === 'active' && (!$mine || in_array($mine['status'], ['declined', 'cancelled'], true))): ?>
      <form method="post" action="/swap/<?= e($l['id']) ?>/propose" class="card stack-sm"><?= csrf_field() ?><p class="lbl"><?= e(L('เสนอแลกด้วยเสื้อผ้าของคุณ', 'Offer one of your garments')) ?></p>
        <?php if ($myGarments): ?><div class="rail"><?php foreach ($myGarments as $g): ?><label class="pickg"><input class="sr-only" type="radio" name="garment_id" value="<?= e($g['id']) ?>" required><?= photo($g['cover_image_id'], $g['category']) ?><strong><?= e($g['name']) ?></strong></label><?php endforeach; ?></div>
          <textarea class="input" name="message" maxlength="500" placeholder="<?= e(L('ข้อความถึงเจ้าของ', 'Message to the owner')) ?>" aria-label="<?= e(L('ข้อความ', 'Message')) ?>"></textarea>
          <button class="btn btn-lg"><?= ic('swap') ?> <?= e(L('ส่งข้อเสนอแลก', 'Send swap offer')) ?></button>
        <?php else: ?><p class="small muted"><?= e(L('เพิ่มเสื้อผ้าพร้อมรูปในตู้ก่อน', 'Add a garment with photos first')) ?> — <a href="/wardrobe/new"><?= e(t('wardrobe.add')) ?></a></p><?php endif; ?></form>
    <?php endif; ?>
    <?php if ($mine): ?><div class="alert <?= in_array($mine['status'], ['mutually_accepted', 'completed'], true) ? 'tone-sage' : '' ?>"><?= e(L('ข้อเสนอของคุณ', 'Your offer')) ?>: <b><?= e($mine['offered_name']) ?></b> · <?= e($rs($mine['status'])) ?>
      <div class="row mt-s"><?php if ($mine['status'] === 'accepted_by_owner') echo $act($mine['id'], 'confirm', L('ยืนยันการแลก', 'Confirm swap'), 'btn-sage btn-sm');
        if ($mine['status'] === 'mutually_accepted') echo $act($mine['id'], 'complete', L('ได้รับของแล้ว', 'I received the item'));
        if ($mine['status'] === 'proposed') echo $act($mine['id'], 'cancel', L('ยกเลิกข้อเสนอ', 'Cancel offer'), 'btn-outline btn-sm'); ?></div></div><?php endif; ?>
    <?php if ($d['isOwner']): ?><div class="card stack-sm"><p class="lbl"><?= e(L('ข้อเสนอที่ได้รับ', 'Offers received')) ?> (<?= count($d['proposals']) ?>)</p>
      <?php foreach ($d['proposals'] as $p): ?><div class="line row"><?= photo($p['offered_image_id'], null, 'mini') ?><div class="grow"><b><?= e($p['requester_name'] ?: L('สมาชิก', 'Member')) ?></b> <?= e(L('เสนอ', 'offers')) ?> <b><?= e($p['offered_name']) ?></b>
        <br><span class="xs muted"><?= e(fmt_date($p['created_at'], true)) ?> · <?= e($rs($p['status'])) ?></span><?php if ($p['message']): ?><p class="small"><?= e($p['message']) ?></p><?php endif; ?></div>
        <?php if ($p['status'] === 'proposed' && $l['status'] === 'active') echo $act($p['id'], 'accept', L('ตอบรับ', 'Accept'), 'btn-sage btn-sm');
          if ($p['status'] === 'mutually_accepted') echo $act($p['id'], 'complete', L('ได้รับของแล้ว', 'I received the item')); ?></div>
      <?php endforeach; ?><?php if (!$d['proposals']): ?><p class="small muted">—</p><?php endif; ?>
      <?php if (in_array($l['status'], ['pending_review', 'active'], true)): ?><form method="post" action="/swap/<?= e($l['id']) ?>/withdraw"><?= csrf_field() ?><button class="btn btn-outline btn-sm"><?= e(L('ถอนประกาศ', 'Withdraw')) ?></button></form><?php endif; ?></div><?php endif; ?>
    <?php if ($actor->isAdmin() && $l['status'] === 'pending_review'): ?><?= View::partial('partials/moderate_form', ['kind' => 'swap', 'id' => $l['id'], 'back' => '/swap/' . $l['id']]) ?><?php endif; ?>
  </div></div></div>
