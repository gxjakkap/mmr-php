<?php use App\Core\View; use App\Domain\AssessmentEngine;
View::$title = null; View::$full = true; View::$bodyClass = 'on-home';
$byKind = []; foreach ($templates as $tp) $byKind[$tp['kind']][] = $tp;
$bySlug = array_column($templates, null, 'slug');
$assessHref = $firstGarment ? "/wardrobe/{$firstGarment}/assess" : '/wardrobe/new';
$qa = [['upload', '/wardrobe/new', L('เพิ่มเสื้อผ้า', 'Add garment')], ['leaf', $assessHref, L('ประเมินศักยภาพ', 'Assess')], ['studio', '/studio', L('ออกแบบใหม่', 'Redesign')],
  ['tag', '/market', L('ตลาดมือสอง', 'Rewear')], ['swap', '/swap', L('แลกเปลี่ยน', 'Swap')], ['recycle', '/learn', L('รีไซเคิล', 'Recycle')]];
$ba = [['jeans', 'skirt', 'jeans-to-skirt'], ['jacket', 'patchwork', 'patchwork-jacket'], ['jeans', 'crossbody', 'crossbody-bag'], ['shirt', 'shirtdress', 'shirt-to-dress']]; ?>
<section class="hero-photo"><img src="<?= e(img('hero')) ?>" alt="<?= e(L('นางแบบสวมแจ็กเก็ตยีนส์แพตช์เวิร์กที่รีดีไซน์จากเสื้อผ้าเดิม', 'Model wearing a patchwork denim jacket redesigned from old clothes')) ?>">
  <div class="in"><p class="kicker">CLOTHING · BAGS · ACCESSORIES · HOME</p>
    <h1><?= L('เสื้อผ้าเดิม<br>สู่ดีไซน์ใหม่<br><em>อย่างยั่งยืน</em>', 'Old clothes,<br>new designs,<br><em>made to last</em>') ?></h1>
    <p class="lead"><?= e(L('เปลี่ยนเสื้อผ้าที่ไม่ได้ใส่ เป็นเสื้อผ้าตัวใหม่ กระเป๋า แอคเซสซอรี่ หรือของแต่งบ้าน — ออกแบบร่วมกับดีไซเนอร์และช่างของเรา', 'Turn clothes you no longer wear into new clothing, bags, accessories or home pieces — designed with our designers and makers')) ?></p>
    <div class="row"><a class="btn btn-gold btn-lg" href="/studio"><?= e(L('เริ่มออกแบบเลย', 'Start designing')) ?> <?= ic('arrow') ?></a>
      <a class="btn btn-glass btn-lg" href="<?= $actor ? '/wardrobe' : '/register' ?>"><?= e($actor ? L('ตู้เสื้อผ้าของฉัน', 'My wardrobe') : L('สมัครสมาชิกฟรี', 'Join free')) ?></a></div></div>
</section>
<div class="wrap">
<nav class="qa" aria-label="<?= e(L('ทางลัด', 'Shortcuts')) ?>"><?php foreach ($qa as [$i, $h, $l]): ?><a href="<?= e($h) ?>"><span class="qa-ic"><?= ic($i) ?></span><span><?= e($l) ?></span></a><?php endforeach; ?></nav>

<section class="sec"><?= section_head(L('ไม่ใช่แค่เสื้อผ้า', 'Not just clothing')) ?>
  <p class="muted small mb-m"><?= e(L('ผ้าจากเสื้อผ้าเดิมของคุณ เปลี่ยนเป็นของชิ้นใหม่ได้หลายแบบ', 'The fabric from your old clothes can become many new things')) ?></p>
  <div class="kinds"><?php foreach (['wear', 'bag', 'acc', 'home'] as $k): $n = $byKind[$k] ?? []; if (!$n) continue; ?>
    <a class="kind" href="/studio?kind=<?= e($k) ?>"><img src="<?= e(img(KIND_IMG[$k])) ?>" alt="" loading="lazy"><span class="kind-t"><b><?= e(kind_label($k)) ?></b>
      <span class="xs"><?= count($n) ?> <?= e(L('แบบ', 'designs')) ?> · <?= e(L('เริ่มต้น', 'from')) ?> ฿<?= number_format(min(array_map(fn($x) => (int) $x['price_min_thb'], $n))) ?></span></span></a>
  <?php endforeach; ?></div></section>

<section class="sec"><?= section_head(L('Inspiration วันนี้', 'Today’s inspiration'), '/studio') ?>
  <div class="rail"><?php foreach ($templates as $tp): ?>
    <div class="insp"><a href="/studio?template=<?= e($tp['id']) ?>"><?= img_box(tpl_img($tp), 'tall', tpl_name($tp)) ?></a>
      <?php if ($actor) echo fav_button('t:' . $tp['slug'], in_array('t:' . $tp['slug'], $favs, true), '/'); ?>
      <strong><?= e(tpl_name($tp)) ?></strong><span class="xs muted"><?= e(L('เริ่มต้น', 'From')) ?> ฿<?= number_format((int) $tp['price_min_thb']) ?></span></div>
  <?php endforeach; ?></div></section>

<section class="sec"><?= section_head('Before & After', '/studio', L('เลือกแบบ', 'Choose a design')) ?>
  <div class="ba-grid"><?php foreach ($ba as [$b, $af, $slug]): $tp = $bySlug[$slug] ?? null; if (!$tp) continue; ?>
    <a class="ba" href="/studio?template=<?= e($tp['id']) ?>"><div class="ba-pair"><?= img_box(img($b), '', 'Before') ?><span class="circle-arr"><?= ic('arrow') ?></span><?= img_box(img($af), '', 'After') ?></div>
      <div><strong><?= e(tpl_name($tp)) ?></strong><br><span class="xs muted"><?= e(App\Core\I18n::locale() === 'th' ? $tp['description_th'] : $tp['description_en']) ?></span></div></a>
  <?php endforeach; ?></div>
  <p class="xs muted mt-s"><?= e(L('ภาพประกอบแนวคิด (สร้างด้วย AI) ไม่ใช่ผลงานจริงของลูกค้า', 'Concept images (AI-generated), not actual customer work')) ?></p></section>

<section class="sec split2"><div class="story-img"><?= img_box(img('atelier'), '', L('ช่างและดีไซเนอร์', 'Atelier')) ?></div>
  <div><p class="kicker dark">HOW IT WORKS</p><h2><?= e(t('home.howTitle')) ?></h2>
    <ol class="steps"><?php foreach (['how1', 'how2', 'how3', 'how4'] as $i => $k): ?><li><span class="stepn"><?= $i + 1 ?></span><p><?= e(t('home.' . $k)) ?></p></li><?php endforeach; ?></ol>
    <p class="alert tone-sage"><?= e(t('home.honesty')) ?></p></div></section>

<?php if ($designers): ?><section class="sec"><?= section_head(L('ดีไซเนอร์ของเรา', 'Our designers')) ?><div class="designers">
  <?php foreach ($designers as $d): ?><div class="designer"><span class="avatar lg"><?= e(mb_substr($d['public_name'], 0, 1)) ?></span><strong><?= e($d['public_name']) ?></strong>
    <span class="xs muted"><?= e(implode(', ', array_map(fn($s) => t('skill.' . $s), json_list($d['specialties'])))) ?></span>
    <?php if ($d['bio']): ?><p class="xs"><?= e(mb_substr($d['bio'], 0, 160)) ?></p><?php endif; ?></div><?php endforeach; ?>
</div></section><?php endif; ?>

<?php if ($listings): ?><section class="sec"><?= section_head(L('ตลาดมือสอง Rewear', 'Rewear marketplace'), '/market') ?><div class="mgrid">
  <?php foreach ($listings as $l) echo View::partial('partials/market_card', ['l' => $l, 'favs' => $favs, 'back' => '/']); ?></div></section><?php endif; ?>

<section class="sec"><?= section_head(t('home.pathwaysTitle'), '/learn', L('เรียนรู้เพิ่ม', 'Learn more')) ?>
  <div class="paths"><?php foreach (AssessmentEngine::PATHWAYS as $p): ?><div class="path"><?= ic(PATH_ICON[$p] ?? 'leaf') ?><strong><?= e(t('pathway.' . $p)) ?></strong><span class="xs muted"><?= e(t('pathwayDesc.' . $p)) ?></span></div><?php endforeach; ?></div></section>

<section class="cta-band"><img src="<?= e(img('model2')) ?>" alt=""><div><h2><?= e(L('ให้เสื้อผ้าที่คุณรัก ได้ไปต่อ', 'Give the clothes you love a next chapter')) ?></h2>
  <p><?= e(L('เริ่มจากถ่ายรูปเสื้อผ้าในตู้ แล้วให้ระบบช่วยแนะนำ', 'Start by photographing a garment — we’ll suggest the options')) ?></p>
  <a class="btn btn-gold btn-lg" href="/wardrobe/new"><?= ic('plus') ?> <?= e(L('เพิ่มเสื้อผ้าชิ้นแรก', 'Add your first garment')) ?></a></div></section>
</div>
