<div class="card"><h3><?= e(t('requests.timeline')) ?></h3>
  <ol class="timeline mt-m">
    <?php if (!$d['history']): ?><li><?= e(t('reqStatus.DRAFT')) ?></li><?php endif; ?>
    <?php foreach ($d['history'] as $h): ?>
      <li><strong><?= e(t('reqStatus.' . $h['to_status'])) ?></strong><br><span class="xs muted"><?= e(fmt_date($h['created_at'], true)) ?></span>
        <?php if ($h['note']): ?><br><span class="xs"><?= e($h['note']) ?></span><?php endif; ?></li>
    <?php endforeach; ?>
  </ol>
</div>
