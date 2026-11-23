<?php /** @var string $kind market|swap @var string $id @var string $back */ ?>
<form method="post" action="/admin/moderate" class="card stack-sm"><?= csrf_field() ?><input type="hidden" name="kind" value="<?= e($kind) ?>"><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="_back" value="<?= e($back) ?>">
  <p class="lbl"><?= e(L('ทีมงาน: ตรวจรูปและการเปิดเผยสภาพก่อนอนุมัติ', 'Staff: check photos and disclosure before approving')) ?></p>
  <?php if ($kind === 'market'): ?><input class="input" name="note" maxlength="1000" placeholder="<?= e(L('หมายเหตุถึงผู้ขาย (ไม่บังคับ)', 'Note to seller (optional)')) ?>" aria-label="<?= e(t('admin.note')) ?>"><?php endif; ?>
  <div class="row"><button class="btn btn-sage btn-sm" name="decision" value="approve"><?= e(L('อนุมัติ', 'Approve')) ?></button><button class="btn btn-outline btn-sm" name="decision" value="reject"><?= e(L('ไม่อนุมัติ', 'Reject')) ?></button></div></form>
