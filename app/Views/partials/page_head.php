<?php /** @var string $title @var ?string $eyebrow @var ?string $body @var ?string $action (pre-escaped HTML) */ ?>
<div class="page-head">
  <div>
    <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($body)): ?><p class="body"><?= e($body) ?></p><?php endif; ?>
  </div>
  <?php if (!empty($action)): ?><div class="row"><?= $action ?></div><?php endif; ?>
</div>
