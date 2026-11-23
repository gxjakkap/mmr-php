<?php use App\Core\View; use App\Domain\Workflow;
$r = $d['request']; \App\Core\View::$title = $r['title']; $self = '/requests/' . $r['id'];
$q = $d['quotations'][0] ?? null;
$v = $q ? (array_values(array_filter($d['versions'], fn($x) => $x['id'] === $q['design_version_id']))[0] ?? null) : null;
$moves = Workflow::nextRequest($r['status'], ['customer']); ?>
<div class="stack">
<?= View::partial('partials/page_head', ['title' => $r['title']]) ?>
<?php if ($d['order']): ?><p class="alert tone-sage"><?= e(t('reqStatus.QUOTE_ACCEPTED')) ?> · <a href="/orders/<?= e($d['order']['id']) ?>"><?= e(t('requests.viewOrder')) ?> <?= e($d['order']['order_no']) ?></a></p><?php endif; ?>
<div class="grid lg-split top">
  <div class="stack">
    <?= View::partial('partials/request/summary', ['d' => $d]) ?>
    <?php if ($q): ?>
      <div class="card">
        <h3><?= e(t('requests.quotation')) ?></h3>
        <div class="mt-m"><?= View::partial('partials/request/quotation', ['q' => $q, 'v' => $v]) ?></div>
        <?php if ((int) $q['is_provisional']): ?><p class="alert xs mt-m"><?= e(t('requests.provisional')) ?></p><?php endif; ?>
        <?php if ($r['status'] === 'QUOTE_SENT' && $q['status'] === 'sent'): ?>
          <div class="grid sm-g2 mt-m">
            <form method="post" action="/quotations/<?= e($q['id']) ?>/respond"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($self) ?>">
              <input type="hidden" name="decision" value="accept"><button class="btn btn-sage btn-block"><?= e(t('requests.accept')) ?></button></form>
            <form method="post" action="/quotations/<?= e($q['id']) ?>/respond" class="stack-sm"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($self) ?>">
              <input type="hidden" name="decision" value="reject">
              <textarea class="input" name="reason" maxlength="2000" placeholder="<?= e(t('requests.rejectReason')) ?>" aria-label="<?= e(t('requests.rejectReason')) ?>"></textarea>
              <button class="btn btn-outline btn-block"><?= e(t('requests.reject')) ?></button></form>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($moves): ?><div class="row"><?= View::partial('partials/transition_buttons', ['moves' => $moves, 'action' => "/requests/{$r['id']}/transition", 'back' => $self, 'kind' => 'request',
      'labels' => ['SUBMITTED' => t('requests.submitDraft'), 'CANCELLED' => t('requests.cancelRequest'), 'UNDER_REVIEW' => t('requests.resubmitInfo')]]) ?></div><?php endif; ?>
  </div>
  <div class="stack">
    <?= View::partial('partials/request/messages', ['d' => $d, 'viewerId' => $actor->id, 'back' => $self]) ?>
    <?= View::partial('partials/request/timeline', ['d' => $d]) ?>
  </div>
</div>
</div>
