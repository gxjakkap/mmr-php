<?php /** @var array $o order @var array $pay settings @var array $slips @var int $paid @var bool $customer @var bool $admin */
$has = $pay['account_no'] !== '' || $pay['promptpay'] !== ''; ?>
<div class="card stack-sm"><h3 class="row gap-s"><?= ic('card') ?> <?= e(L('ชำระเงิน', 'Payment')) ?></h3>
<?php if ($customer && $o['status'] === 'AWAITING_PAYMENT'): ?>
  <?php if ($has): ?><div class="bank"><p class="xs"><?= e(L('โอนเงินเข้าบัญชี', 'Transfer to')) ?></p><p><b><?= e($pay['bank']) ?></b></p>
    <?php if ($pay['account_no'] !== ''): ?><p class="big tabular"><?= e($pay['account_no']) ?></p><?php endif; ?><p class="small"><?= e($pay['account_name']) ?></p>
    <?php if ($pay['promptpay'] !== ''): ?><p class="small">PromptPay: <span class="tabular"><?= e($pay['promptpay']) ?></span></p><?php endif; ?>
    <?php if ($pay['note'] !== ''): ?><p class="xs mt-s"><?= e($pay['note']) ?></p><?php endif; ?></div>
  <?php else: ?><p class="alert"><?= e(t('orders.payInstructions')) ?></p><?php endif; ?>
  <p class="small"><?= e(L('ยอดที่ต้องชำระ', 'Amount due')) ?>: <b class="tabular"><?= e(money(max((int) $o['total_satang'] - $paid, 0))) ?></b></p>
  <form method="post" action="/orders/<?= e($o['id']) ?>/slip" enctype="multipart/form-data" class="row"><?= csrf_field() ?>
    <input id="slip" type="file" name="slip" required accept="image/jpeg,image/png,image/webp" aria-label="<?= e(L('สลิปการโอน', 'Transfer slip')) ?>" data-max-bytes="<?= App\Core\Upload::MAX_BYTES ?>" data-max-files="1"
      data-msg-type="<?= e(t('wardrobe.photoBadType')) ?>" data-msg-size="<?= e(t('wardrobe.photoTooBig')) ?>" data-msg-count="<?= e(t('wardrobe.tooManyPhotos')) ?>">
    <button class="btn btn-outline btn-sm"><?= ic('upload') ?> <?= e(L('แนบสลิปการโอน', 'Attach transfer slip')) ?></button><span id="slip-error" class="hint" role="alert"></span></form>
  <p class="xs muted"><?= e(L('สลิปเป็นหลักฐานประกอบเท่านั้น ยอดจะถือว่าชำระเมื่อทีมงานตรวจและยืนยันแล้ว', 'A slip is supporting evidence only — the payment counts once our team has checked and confirmed it.')) ?></p>
<?php endif; ?>
<?php if ($slips): ?><p class="lbl"><?= e(L('สลิปที่แนบ', 'Attached slips')) ?></p><div class="slips"><?php foreach ($slips as $s): ?><a class="slip" href="/media/slip/<?= e($s['id']) ?>" target="_blank" rel="noopener"><img src="/media/slip/<?= e($s['id']) ?>" alt="<?= e(L('สลิป', 'Slip')) ?>"><span class="xs"><?= e(fmt_date($s['created_at'], true)) ?></span></a><?php endforeach; ?></div><?php endif; ?>
<p class="small tabular"><?= e(t('requests.total')) ?> <?= e(money((int) $o['total_satang'])) ?> · <?= e(t('orders.paidTotal')) ?> <?= e(money($paid)) ?></p>
</div>
