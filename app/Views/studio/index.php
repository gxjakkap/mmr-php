<?php use App\Core\{I18n, View}; View::$title = t('studio.title'); $th = I18n::locale() === 'th';
$link = fn(array $p) => url('/studio', $p + ['garment' => $garment['id'] ?? null, 'template' => $template['id'] ?? null, 'mode' => $mode, 'kind' => $kind, 'fit' => $fit ? '1' : null]);
$ready = $garment && ($mode === 'custom' || $template);
$incompatible = $garment && $template && !in_array($garment['category'], json_list($template['compatible_categories']), true);
$list = array_values(array_filter($templates, fn($x) => (!$kind || $x['kind'] === $kind) && (!$fit || !$garment || in_array($garment['category'], json_list($x['compatible_categories']), true))));
$counts = array_count_values(array_column($templates, 'kind'));
$self = $_SERVER['REQUEST_URI'] ?? '/studio'; ?>
<?= photo_banner(L('เปลี่ยนเสื้อผ้าเดิม เป็นของชิ้นใหม่', 'Turn old clothes into something new'),
  L('เสื้อผ้า กระเป๋า แอคเซสซอรี่ หรือของแต่งบ้าน — เลือกเทมเพลต หรือส่งไอเดียของคุณให้ดีไซเนอร์', 'Clothing, bags, accessories or home pieces — pick a template or send your idea to a designer'), 'denim',
  '<div class="banner-pics"><img src="' . e(img('jeans')) . '" alt="">' . ic('arrow') . '<img src="' . e(img('crossbody')) . '" alt=""></div>') ?>
<nav class="segtabs" aria-label="mode">
  <?php foreach (['template' => L('เทมเพลตแนะนำ', 'Templates'), 'custom' => L('ออกแบบเฉพาะ', 'Custom'), 'ai' => L('AI สร้างไอเดีย', 'AI ideas')] as $m => $label): ?>
    <a href="<?= e($link(['mode' => $m])) ?>"<?= $m === $mode ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php if ($mode === 'ai'): ?>
  <div class="ai-card"><img src="<?= e(img('model2')) ?>" alt=""><div><p class="kicker dark">AI IMAGE GENERATION</p><h2><?= e(L('สร้างภาพไอเดียด้วย AI', 'Generate concept images with AI')) ?></h2>
    <p class="muted mt-s"><?= e(t('studio.aiUnavailable')) ?></p><a class="btn mt-m" href="<?= e($link(['mode' => 'custom'])) ?>"><?= e(L('ไปที่ออกแบบเฉพาะ', 'Go to Custom')) ?></a></div></div>
<?php else: ?>
  <section class="sec"><?= section_head('1 · ' . t('studio.chooseGarment')) ?>
    <?php if (!$actor): ?>
      <div class="ocard"><span class="pi"><?= ic('user') ?></span><div class="grow"><strong><?= e(L('เข้าสู่ระบบเพื่อเลือกเสื้อผ้าของคุณ', 'Sign in to choose your garment')) ?></strong><p class="xs muted"><?= e(L('ดูแบบทั้งหมดได้เลยโดยไม่ต้องสมัคร', 'You can browse every design without an account')) ?></p></div>
        <a class="btn btn-gold btn-sm" href="/login?next=<?= e(rawurlencode($self)) ?>"><?= e(t('common.signIn')) ?></a></div>
    <?php elseif (!$garments): ?>
      <?= View::partial('partials/empty', ['title' => t('studio.noGarments'), 'action' => '<a class="btn btn-gold" href="/wardrobe/new">' . ic('plus') . ' ' . e(t('wardrobe.add')) . '</a>']) ?>
    <?php else: ?>
      <div class="rail"><?php foreach ($garments as $g): $on = ($garment['id'] ?? '') === $g['id']; ?>
        <a class="pickg<?= $on ? ' on' : '' ?>" href="<?= e($link(['garment' => $g['id']])) ?>"<?= $on ? ' aria-current="true"' : '' ?>><?= photo($g['cover_image_id'], $g['category']) ?>
          <strong><?= e($g['name']) ?></strong><span class="xs muted"><?= e(t('category.' . $g['category'])) ?></span><?php if ($on): ?><span class="picked"><?= ic('check') ?></span><?php endif; ?></a>
      <?php endforeach; ?></div>
    <?php endif; ?>
  </section>
  <?php if ($mode === 'template'): ?>
  <section class="sec"><?= section_head('2 · ' . t('studio.chooseTemplate')) ?>
    <nav class="kindtabs" aria-label="<?= e(L('ประเภทผลงาน', 'Product type')) ?>">
      <a class="ktab" href="<?= e($link(['kind' => null])) ?>"<?= !$kind ? ' aria-current="page"' : '' ?>><?= ic('sparkle') ?><span><?= e(L('ทั้งหมด', 'All')) ?></span><small><?= count($templates) ?></small></a>
      <?php foreach (['wear', 'bag', 'acc', 'home'] as $k): ?><a class="ktab" href="<?= e($link(['kind' => $k])) ?>"<?= $kind === $k ? ' aria-current="page"' : '' ?>><?= ic(KIND_ICON[$k]) ?><span><?= e(kind_label($k)) ?></span><small><?= (int) ($counts[$k] ?? 0) ?></small></a><?php endforeach; ?>
    </nav>
    <?php if ($garment): ?><div class="pills"><a class="btn btn-chip" href="<?= e($link(['fit' => null])) ?>"<?= !$fit ? ' aria-current="page"' : '' ?>><?= e(L('ทุกแบบ', 'Every design')) ?></a>
      <a class="btn btn-chip" href="<?= e($link(['fit' => '1'])) ?>"<?= $fit ? ' aria-current="page"' : '' ?>><?= e(L('เฉพาะที่ทำจาก', 'Only fits')) ?> “<?= e($garment['name']) ?>”</a></div><?php endif; ?>
    <div class="tcards mt-m"><?php foreach ($list as $tp): $on = ($template['id'] ?? '') === $tp['id']; ?>
      <div class="tcard<?= $on ? ' on' : '' ?>"><a href="<?= e($link(['template' => $tp['id']])) ?>#request"<?= $on ? ' aria-current="true"' : '' ?>><?= img_box(tpl_img($tp), 'tall', tpl_name($tp)) ?>
        <strong><?= e(tpl_name($tp)) ?></strong><span class="xs muted"><?= e($th ? $tp['description_th'] : $tp['description_en']) ?></span>
        <span class="row xs gap-s"><span class="badge"><?= e(kind_label($tp['kind'])) ?></span><span class="badge <?= ['high' => 'tone-danger', 'medium' => 'tone-gold', 'low' => 'tone-sage'][$tp['difficulty']] ?? '' ?>"><?= e(t('level.' . $tp['difficulty'])) ?></span>
          <span class="tabular">฿<?= number_format((int) $tp['price_min_thb']) ?>–<?= number_format((int) $tp['price_max_thb']) ?></span></span></a>
        <?php if ($actor) echo fav_button('t:' . $tp['slug'], in_array('t:' . $tp['slug'], $favs, true), $self); ?>
        <?php if ($on): ?><span class="picked"><?= ic('check') ?></span><?php endif; ?></div>
    <?php endforeach; ?><?php if (!$list): ?><p class="muted">—</p><?php endif; ?></div>
  </section>
  <?php endif; ?>
  <?php if ($ready): $note = $template ? ($th ? $template['feasibility_note_th'] : $template['feasibility_note_en']) : ''; ?>
  <section class="reqform" id="request">
    <div class="compare"><div><?= photo($garment['cover_image_id'], $garment['category']) ?><p class="xs muted"><?= e(t('studio.original')) ?></p><strong><?= e($garment['name']) ?></strong></div>
      <span class="circle-arr lg"><?= ic('arrow') ?></span>
      <div><?= $template ? img_box(tpl_img($template)) : img_box(img('atelier')) ?><p class="xs muted"><?= e(t('studio.proposed')) ?><?= $template ? ' · ' . e(L('ภาพตัวอย่าง', 'sample')) : '' ?></p><strong><?= e(tpl_name($template)) ?></strong></div></div>
    <div class="card stack-sm"><h2><?= $mode === 'template' ? '3' : '2' ?> · <?= e(t('studio.submitRequest')) ?></h2>
      <?php if ($template): ?><div class="facts"><span><?= ic('box') ?> <?= e(t('studio.extraMaterials')) ?><b><?= e(implode(', ', json_list($template['extra_materials']))) ?></b></span>
        <span><?= ic('chart') ?> <?= e(t('studio.difficulty')) ?><b><?= e(t('level.' . $template['difficulty'])) ?></b></span>
        <span><?= ic('tag') ?> <?= e(t('studio.priceRange')) ?><b class="tabular">฿<?= number_format((int) $template['price_min_thb']) ?>–<?= number_format((int) $template['price_max_thb']) ?></b></span></div>
      <?php else: ?><p class="small muted"><?= e(t('studio.customIntro')) ?></p><?php endif; ?>
      <?php if ($incompatible): ?><p class="alert tone-danger"><?= e(t('studio.incompatible')) ?></p><?php endif; ?>
      <p class="alert"><?= e(t('studio.feasibility')) ?>: <?= e($note !== '' ? $note : t('home.honesty')) ?></p>
      <form method="post" action="/studio" class="stack-sm">
        <?= csrf_field() ?>
        <input type="hidden" name="garment_id" value="<?= e($garment['id']) ?>"><input type="hidden" name="template_id" value="<?= e($template['id'] ?? '') ?>"><input type="hidden" name="mode" value="<?= e($mode) ?>">
        <label class="field"><span><?= e(t('studio.requestTitle')) ?></span><input class="input" name="title" required maxlength="120" value="<?= e($old['title'] ?? mb_substr($garment['name'] . ' → ' . tpl_name($template), 0, 120)) ?>"></label>
        <label class="field"><span><?= e(t('studio.requirements')) ?></span><textarea class="input" name="requirements" maxlength="4000"<?= $mode === 'custom' ? ' required' : '' ?> placeholder="<?= e(L('เช่น ขนาด ทรง สี รายละเอียดที่อยากเก็บไว้', 'e.g. size, shape, colour, details to keep')) ?>"><?= e($old['requirements'] ?? '') ?></textarea></label>
        <label class="field"><span><?= e(t('studio.budget')) ?></span><input class="input" type="number" name="budget_max_thb" min="0" max="1000000" inputmode="numeric" value="<?= e($old['budget_max_thb'] ?? '') ?>"><span class="hint"><?= e(t('common.optional')) ?></span></label>
        <label class="check"><input type="checkbox" name="acknowledge" value="1" required> <?= e(t('studio.acknowledge')) ?></label>
        <div class="row"><button class="btn btn-lg" name="submit" value="1"><?= e(t('studio.submitRequest')) ?> <?= ic('arrow') ?></button><button class="btn btn-outline btn-lg" name="submit" value="0"><?= e(t('studio.saveDraft')) ?></button></div>
      </form></div>
  </section>
  <?php endif; ?>
<?php endif; ?>
