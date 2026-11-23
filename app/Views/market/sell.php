<?php use App\Core\View; View::$title = L('ลงขายมือสอง', 'Sell on Rewear');
$disc = L('สภาพ: ', 'Condition: ') . t('condition.' . $g['condition']) . ' · ' . (($d = json_list($g['defects'])) ? L('ตำหนิ: ', 'Flaws: ') . implode(', ', array_map(fn($x) => t('defect.' . $x), $d)) : L('ไม่มีตำหนิที่ระบุ (โปรดตรวจอีกครั้ง)', 'No flaws recorded (please double-check)')) . ($g['condition_notes'] ? ' · ' . $g['condition_notes'] : ''); ?>
<div class="w-md stack">
<a class="btn btn-ghost btn-sm" href="/wardrobe/<?= e($g['id']) ?>"><?= ic('back') ?> <?= e($g['name']) ?></a>
<h1><?= e(L('ลงขายมือสอง (Rewear)', 'Sell on Rewear')) ?></h1>
<div class="ocard"><?= photo($images[0]['id'] ?? null, $g['category'], 'mini') ?><div class="grow"><strong><?= e($g['name']) ?></strong><p class="xs muted"><?= e(t('category.' . $g['category'])) ?></p></div><?= cond_badge($g['condition']) ?></div>
<?php if (!$images): ?><p class="alert tone-danger"><?= e(L('ต้องมีรูปจริงอย่างน้อย 1 รูปก่อนลงขาย', 'Add at least one real photo before listing')) ?> — <a href="/wardrobe/<?= e($g['id']) ?>"><?= e(t('wardrobe.addPhotos')) ?></a></p><?php endif; ?>
<form method="post" action="/wardrobe/<?= e($g['id']) ?>/sell" class="card stack-sm"><?= csrf_field() ?>
  <label class="field"><span><?= e(L('ชื่อประกาศ', 'Listing title')) ?></span><input class="input" name="title" required maxlength="120" value="<?= e($old['title'] ?? $g['name']) ?>"></label>
  <div class="grid sm-g2"><label class="field"><span><?= e(L('ราคา (บาท)', 'Price (THB)')) ?></span><input class="input" name="price" required inputmode="decimal" pattern="\d{1,7}(\.\d{1,2})?" value="<?= e($old['price'] ?? '') ?>"></label>
    <label class="field"><span><?= e(L('ไซซ์', 'Size')) ?></span><input class="input" name="size" maxlength="20" placeholder="S / M / L / 30" value="<?= e($old['size'] ?? '') ?>"></label></div>
  <label class="field"><span><?= e(L('เปิดเผยสภาพตามจริง', 'Honest condition disclosure')) ?></span><textarea class="input" name="disclosure" required minlength="10" maxlength="2000"><?= e($old['disclosure'] ?? $disc) ?></textarea>
    <span class="hint"><?= e(L('ระบุตำหนิทุกจุด ประกาศจะผ่านการตรวจโดยทีมงานก่อนแสดง', 'List every flaw; our team reviews listings before they go live')) ?></span></label>
  <button class="btn btn-lg"<?= $images ? '' : ' disabled' ?>><?= ic('tag') ?> <?= e(L('ส่งตรวจและลงขาย', 'Submit for review')) ?></button></form>
</div>
