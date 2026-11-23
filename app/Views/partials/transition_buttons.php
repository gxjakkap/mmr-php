<?php
/** Renders one POST form per allowed move. @var array $moves @var string $action @var string $back @var string $kind @var array $labels */
foreach ($moves as $to): ?>
  <form method="post" action="<?= e($action) ?>" class="inline">
    <?= csrf_field() ?><input type="hidden" name="to" value="<?= e($to) ?>"><input type="hidden" name="_back" value="<?= e($back) ?>">
    <?php if (!empty($withNote)): ?><input class="input" name="note" maxlength="2000" placeholder="<?= e(t('admin.note')) ?>" aria-label="<?= e(t('admin.note')) ?>"><?php endif; ?>
    <button class="btn <?= $to === 'CANCELLED' ? 'btn-ghost' : 'btn-outline' ?>"><?= e($labels[$to] ?? t(($kind === 'order' ? 'orderStatus.' : 'reqStatus.') . $to)) ?></button>
  </form>
<?php endforeach;
