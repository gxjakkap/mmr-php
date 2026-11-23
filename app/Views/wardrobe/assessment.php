<?php use App\Core\View; \App\Core\View::$title = t('assess.resultTitle');
$tone = ['recommended' => 'tone-sage', 'possible' => 'tone-gold', 'not_advised' => '']; ?>
<div class="stack">
<?= View::partial('partials/page_head', ['eyebrow' => $a['garment_name'], 'title' => t('assess.resultTitle'),
  'action' => '<a class="btn btn-outline" href="/wardrobe/' . e($a['garment_id']) . '/assess">' . e(t('assess.again')) . '</a>']) ?>
<p class="alert"><?= e(t('assess.preliminary')) ?></p>
<p class="xs muted"><?= e(t('assess.noScore')) ?> · <?= e(t('assess.engine')) ?>: <?= e($a['engine_version']) ?> · <?= e(fmt_date($a['created_at'], true)) ?></p>
<ol class="grid sm-g2 list-reset">
  <?php foreach ($a['recommendations'] as $r): ?>
  <li class="card">
    <div class="row between"><h3><?= e(t('pathway.' . $r['pathway'])) ?></h3><span class="badge <?= $tone[$r['suitability']] ?>"><?= e(t('suitability.' . $r['suitability'])) ?></span></div>
    <dl class="kv xs mt-s">
      <?php if ($r['complexity']): ?><dt><?= e(t('assess.complexity')) ?></dt><dd><?= e(t('level.' . $r['complexity'])) ?></dd><?php endif; ?>
      <dt><?= e(t('assess.uncertainty')) ?></dt><dd><?= e(t('level.' . $r['uncertainty'])) ?></dd>
      <?php if ($r['cost_min_thb'] !== null): ?><dt><?= e(t('assess.cost')) ?></dt><dd>฿<?= number_format((int) $r['cost_min_thb']) ?>–<?= number_format((int) $r['cost_max_thb']) ?></dd><?php endif; ?>
    </dl>
    <div class="grid sm-g2 small mt-s">
      <?php if ($adv = json_list($r['advantages'])): ?><div><p class="xs"><strong><?= e(t('assess.advantages')) ?></strong></p><ul class="bullets"><?php foreach ($adv as $k): ?><li><?= e(t('rec.' . $k)) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <?php if ($dis = json_list($r['disadvantages'])): ?><div><p class="xs"><strong><?= e(t('assess.disadvantages')) ?></strong></p><ul class="bullets"><?php foreach ($dis as $k): ?><li><?= e(t('rec.' . $k)) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    </div>
    <?php if ($mat = json_list($r['materials'])): ?><p class="xs mt-s"><span class="muted"><?= e(t('assess.materials')) ?>:</span> <?= e(implode(', ', array_map(fn($k) => t('rec.' . $k), $mat))) ?></p><?php endif; ?>
    <?php if ($next = json_list($r['next_steps'])): ?><div class="alert xs mt-s"><strong><?= e(t('assess.nextSteps')) ?></strong><?php foreach ($next as $k): ?><br>→ <?= e(t('rec.' . $k)) ?><?php endforeach; ?></div><?php endif; ?>
    <details class="xs muted mt-s"><summary><?= e(t('assess.rules')) ?></summary><code><?= e(implode(' · ', json_list($r['rationale']))) ?></code></details>
  </li>
  <?php endforeach; ?>
</ol>
<div class="row">
  <a class="btn btn-gold" href="<?= e(url('/studio', ['garment' => $a['garment_id']])) ?>"><?= e(t('wardrobe.startDesign')) ?></a>
  <a class="btn btn-ghost" href="/wardrobe/<?= e($a['garment_id']) ?>"><?= e(t('common.back')) ?></a>
</div>
</div>
