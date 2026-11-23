<?php $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); ?>
<nav class="scroll-x mb-m" aria-label="admin">
  <?php foreach (['/admin' => t('admin.overview'), '/admin/requests' => t('admin.requests'), '/admin/orders' => t('admin.orders'), '/admin/moderation' => L('ตรวจประกาศ', 'Moderation'),
    '/admin/users' => t('admin.users'), '/admin/settings' => L('ตั้งค่า', 'Settings'), '/admin/audit' => t('admin.audit')] as $h => $l): ?>
    <a class="btn btn-chip" href="<?= e($h) ?>"<?= $p === $h ? ' aria-current="page"' : '' ?>><?= e($l) ?></a>
  <?php endforeach; ?>
</nav>
