<?php
/** @var string $status @var string $kind request|order */
$tones = ['SUBMITTED' => 'gold', 'UNDER_REVIEW' => 'gold', 'FEASIBILITY_REVIEW' => 'gold', 'QUOTE_PREPARING' => 'gold', 'NEEDS_INFORMATION' => 'danger',
  'QUOTE_SENT' => 'sage', 'QUOTE_ACCEPTED' => 'sage', 'AWAITING_PAYMENT' => 'danger', 'REWORK_REQUIRED' => 'danger', 'REFUND_REVIEW' => 'danger',
  'COMPLETED' => 'sage', 'DELIVERED' => 'sage'];
$tone = $tones[$status] ?? '';
?><span class="badge <?= $tone ? 'tone-' . $tone : '' ?>"><?= e(t(($kind === 'order' ? 'orderStatus.' : 'reqStatus.') . $status)) ?></span>
