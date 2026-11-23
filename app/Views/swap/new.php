<?php use App\Core\View; View::$title = L('ลงแลกเปลี่ยน', 'List for swap'); $d = json_list($g['defects']);
$disc = L('สภาพ: ', 'Condition: ') . t('condition.' . $g['condition']) . ' · ' . ($d ? L('ตำหนิ: ', 'Flaws: ') . implode(', ', array_map(fn($x) => t('defect.' . $x), $d)) : L('ไม่มีตำหนิที่ระบุ (โปรดตรวจอีกครั้ง)', 'No flaws recorded (please double-check)')); ?>
<div class="w-md stack">
<a class="btn btn-ghost btn-sm" href="/wardrobe/<?= e($g['id']) ?>"><?= ic('back') ?> <?= e($g['name']) ?></a>
<h1><?= e(L('ลงแลกเปลี่ยน', 'List for swap')) ?></h1>
<div class="ocard"><?= photo($images[0]['id'] ?? null, $g['category'], 'mini') ?><div class="grow"><strong><?= e($g['name']) ?></strong></div><?= cond_badge($g['condition']) ?></div>
<?php if (!$images): ?><p class="alert tone-danger"><?= e(L('ต้องมีรูปจริงอย่างน้อย 1 รูป', 'Add at least one real photo first')) ?></p><?php endif; ?>
<form method="post" action="/wardrobe/<?= e($g['id']) ?>/swap" class="card stack-sm"><?= csrf_field() ?>
  <label class="field"><span><?= e(L('อยากแลกกับอะไร', 'What are you looking for?')) ?></span><input class="input" name="looking_for" maxlength="200" placeholder="<?= e(L('เช่น กระโปรงยีนส์ ไซซ์ M', 'e.g. denim skirt, size M')) ?>" value="<?= e($old['looking_for'] ?? '') ?>"></label>
  <label class="field"><span><?= e(L('เปิดเผยสภาพตามจริง', 'Honest condition disclosure')) ?></span><textarea class="input" name="disclosure" required minlength="10" maxlength="2000"><?= e($old['disclosure'] ?? $disc) ?></textarea></label>
  <button class="btn btn-lg"<?= $images ? '' : ' disabled' ?>><?= ic('swap') ?> <?= e(L('ส่งตรวจและลงแลก', 'Submit for review')) ?></button></form></div>
