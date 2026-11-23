<?php use App\Core\View; View::$title = L('การแจ้งเตือน', 'Notifications');
$text = function (array $n): string {
    return match ($n['kind']) {
        'req' => $n['title'] . ' · ' . t('reqStatus.' . $n['status']),
        'msg' => L('ข้อความใหม่ใน ', 'New message in ') . $n['title'] . ': ' . $n['body'],
        'order' => $n['title'] . ' · ' . t('orderStatus.' . $n['status']),
        'buy_request' => L('มีคนขอซื้อ: ', 'Purchase request: ') . $n['title'],
        'buy_accepted' => $n['title'] . ' · ' . L('ผู้ขายตอบรับแล้ว', 'seller accepted'),
        'buy_declined' => $n['title'] . ' · ' . L('ผู้ขายปฏิเสธ', 'seller declined'),
        'listing_active' => $n['title'] . ' · ' . L('อนุมัติแล้ว แสดงให้สมาชิกเห็นแล้ว', 'approved and now live'),
        'listing_rejected' => $n['title'] . ' · ' . L('ไม่ผ่านการตรวจ', 'not approved'),
        'swap_offer' => L('มีข้อเสนอแลก: ', 'Swap offer: ') . $n['title'],
        'swap_accepted_by_owner' => $n['title'] . ' · ' . L('เจ้าของตอบรับข้อเสนอ — กดยืนยันเพื่อแลก', 'owner accepted — confirm to go ahead'),
        'swap_completed' => $n['title'] . ' · ' . L('แลกสำเร็จ', 'swap completed'),
        'swap_declined' => $n['title'] . ' · ' . L('ข้อเสนอไม่ได้รับเลือก', 'offer not selected'),
        default => (string) $n['title'] . ' · ' . listing_status_label(substr($n['kind'], 8)),
    };
}; ?>
<div class="w-md">
<div class="row between mb-m"><div><h1><?= e(L('การแจ้งเตือน', 'Notifications')) ?></h1><p class="muted small mt-s"><?= e(L('อัปเดตจากคำขอ คำสั่งซื้อ ตลาด และการแลก', 'Updates from requests, orders, marketplace and swaps')) ?></p></div>
<?php if ($items): ?><form method="post" action="/inbox/seen"><?= csrf_field() ?><button class="btn btn-outline btn-sm"><?= ic('check') ?> <?= e(L('อ่านทั้งหมด', 'Mark all read')) ?></button></form><?php endif; ?></div>
<?php if ($items): ?><div class="inbox"><?php foreach ($items as $n): $new = !$seen || $n['at'] > $seen; ?>
  <a class="nitem<?= $new ? ' new' : '' ?>" href="<?= e($n['href']) ?>"><span class="ni"><?= ic($n['icon']) ?></span><span class="grow"><?= e($text($n)) ?><br><span class="xs muted"><?= e(fmt_date($n['at'], true)) ?></span></span><?php if ($new): ?><span class="ndot"></span><?php endif; ?></a>
<?php endforeach; ?></div>
<?php else: ?><?= View::partial('partials/empty', ['title' => L('ยังไม่มีการแจ้งเตือน', 'No notifications yet')]) ?><?php endif; ?></div>
