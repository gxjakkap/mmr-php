<?php /** @var array $d @var string $viewerId @var string $back @var bool $allowInternal */ ?>
<div class="card"><h3><?= e(t('requests.messages')) ?></h3>
  <ul class="list-reset stack-sm mt-m">
    <?php if (!$d['messages']): ?><li class="small muted"><?= e(t('requests.noMessages')) ?></li><?php endif; ?>
    <?php foreach ($d['messages'] as $m): ?>
      <li class="bubble <?= $m['sender_id'] === $viewerId ? 'me' : 'them' ?>">
        <?php if ($m['is_internal']): ?><span class="badge tone-danger"><?= e(t('requests.internal')) ?></span><br><?php endif; ?>
        <span class="xs"><strong><?= e($m['display_name']) ?></strong></span>
        <p class="pre"><?= e($m['body']) ?></p><p class="xs muted"><?= e(fmt_date($m['created_at'], true)) ?></p>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($d['conversation_id']): ?>
  <form method="post" action="/requests/<?= e($d['request']['id']) ?>/message" class="stack-sm mt-m">
    <?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>">
    <textarea class="input" name="body" required maxlength="4000" placeholder="<?= e(t('requests.messagePlaceholder')) ?>" aria-label="<?= e(t('requests.messagePlaceholder')) ?>"></textarea>
    <div class="row between">
      <?php if (!empty($allowInternal)): ?><label class="check xs"><input type="checkbox" name="internal" value="1"> <?= e(t('requests.internal')) ?></label><?php else: ?><span></span><?php endif; ?>
      <button class="btn btn-sm"><?= e(t('requests.send')) ?></button>
    </div>
  </form>
  <?php endif; ?>
</div>
