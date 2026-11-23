<?php
declare(strict_types=1);
// v3 features: product kinds, Rewear, Swap, favourites, outfits, notifications, payment settings and slips.
use App\Core\{Db, Policy};
use App\Services\{GarmentService, MarketService, NotificationService, PaymentService, StyleService, SwapService, UserService, RequestService};

$db = Db::get();
$gs = new GarmentService($db); $m = new MarketService($db); $sw = new SwapService($db); $st = new StyleService($db);
$seller = make_user('seller@v3.test'); $buyer = make_user('buyer@v3.test'); $third = make_user('third@v3.test'); $boss = make_user('boss@v3.test', ['admin']);
$mkG = function ($who, string $name, string $cat = 'denim_jacket', bool $photo = true) use ($gs) {
    $id = $gs->create($who, ['name' => $name, 'category' => $cat, 'fabric_primary' => 'denim', 'condition' => 'good']);
    if ($photo) { $p = tmp_png(); $gs->addImages($who, $id, [['tmp_name' => $p, 'size' => filesize($p), 'error' => 0]]); }
    return $id;
};

suite('Product kinds');
test('templates cover clothing, bags, accessories and home goods', function () {
    $kinds = array_count_values(array_column((new UserService(Db::get()))->templates(), 'kind'));
    ok(($kinds['wear'] ?? 0) >= 9 && ($kinds['bag'] ?? 0) >= 3 && ($kinds['acc'] ?? 0) >= 3 && ($kinds['home'] ?? 0) >= 2, json_encode($kinds));
    foreach ((new UserService(Db::get()))->templates() as $t) ok($t['image_key'] !== '' && is_file(BASE_PATH . '/public/assets/img/' . $t['image_key'] . '.jpg'), 'image for ' . $t['slug']);
});

suite('Rewear marketplace');
$G = ''; $L = ''; $offer = '';
test('listing needs a real photo, a valid price and a disclosure', function () use ($mkG, $m, $seller, &$G) {
    $noPhoto = $mkG($seller, 'No photo', 'jeans', false);
    throws(fn() => $m->create($seller, $noPhoto, ['price' => '500', 'disclosure' => 'Good condition, no flaws']), 'photo_required');
    $G = $mkG($seller, 'Blue jacket');
    throws(fn() => $m->create($seller, $G, ['price' => 'abc', 'disclosure' => 'Good condition, no flaws']), 'invalid_price');
    throws(fn() => $m->create($seller, $G, ['price' => '500', 'disclosure' => 'ok']), 'disclosure');
});
test('cannot list someone else’s garment', function () use ($m, $buyer, &$G) {
    throws(fn() => $m->create($buyer, $G, ['price' => '500', 'disclosure' => 'Good condition, no flaws']), 'not found');
});
test('new listing is pending and hidden from other members until approved', function () use ($m, $seller, $buyer, $boss, &$G, &$L) {
    $L = $m->create($seller, $G, ['price' => '650.50', 'size' => 'M', 'disclosure' => 'Good condition, small fade on cuff']);
    eq('pending_review', Db::get()->value('SELECT status FROM marketplace_listings WHERE id = ?', [$L]));
    eq(65050, (int) Db::get()->value('SELECT price_satang FROM marketplace_listings WHERE id = ?', [$L]));
    ok(!array_filter($m->browse(null, null), fn($r) => $r['id'] === $L), 'not in browse');
    throws(fn() => $m->get($buyer, $L), 'not found');
    ok(!Policy::canViewGarment(Db::get(), $buyer, $G), 'photo hidden');
    throws(fn() => $m->moderate($buyer, $L, true), 'admin');
    $m->moderate($boss, $L, true);
    ok((bool) array_filter($m->browse(null, null), fn($r) => $r['id'] === $L), 'visible after approval');
    ok(Policy::canViewGarment(Db::get(), $buyer, $G), 'photo visible to members after approval');
    throws(fn() => $m->moderate($boss, $L, false), 'not_pending');
});
test('the same garment cannot be listed twice or swapped while listed', function () use ($m, $sw, $seller, &$G) {
    throws(fn() => $m->create($seller, $G, ['price' => '500', 'disclosure' => 'Good condition, no flaws']), 'garment_busy');
    throws(fn() => $sw->create($seller, $G, ['disclosure' => 'Good condition, no flaws']), 'garment_busy');
});
test('buyer requests; seller cannot buy own item; duplicate request blocked', function () use ($m, $seller, $buyer, &$L, &$offer) {
    throws(fn() => $m->requestToBuy($seller, $L, ''), 'own listing');
    $m->requestToBuy($buyer, $L, 'pick up at BTS?');
    throws(fn() => $m->requestToBuy($buyer, $L, ''), 'already_requested');
    $offer = Db::get()->value('SELECT id FROM marketplace_orders WHERE listing_id = ? AND buyer_id = ?', [$L, $buyer->id]);
    eq(65050, (int) Db::get()->value('SELECT price_satang FROM marketplace_orders WHERE id = ?', [$offer]));
});
test('only the seller decides; accepting reserves and declines the rest', function () use ($m, $seller, $buyer, $third, &$L, &$offer) {
    $m->requestToBuy($third, $L, '');
    throws(fn() => $m->decide($buyer, $offer, true), 'not found');
    $m->decide($seller, $offer, true);
    eq('reserved', Db::get()->value('SELECT status FROM marketplace_listings WHERE id = ?', [$L]));
    eq('declined', Db::get()->value('SELECT status FROM marketplace_orders WHERE listing_id = ? AND buyer_id = ?', [$L, $third->id]));
    throws(fn() => $m->requestToBuy($third, $L, ''), 'not_available');
    throws(fn() => $m->setStatus($buyer, $L, 'sold'), 'not found');
    $m->setStatus($seller, $L, 'sold');
    eq('completed', Db::get()->value('SELECT status FROM marketplace_orders WHERE id = ?', [$offer]));
    throws(fn() => $m->setStatus($seller, $L, 'withdrawn'), 'not_allowed');
});
test('buyer and seller are notified from the history tables', function () use ($seller, $buyer, $third) {
    $n = new NotificationService(Db::get());
    ok((bool) array_filter($n->list($seller), fn($x) => $x['kind'] === 'buy_request'), 'seller sees request');
    ok((bool) array_filter($n->list($seller), fn($x) => $x['kind'] === 'listing_active'), 'seller sees approval');
    ok((bool) array_filter($n->list($buyer), fn($x) => $x['kind'] === 'buy_accepted'), 'buyer sees acceptance');
    ok((bool) array_filter($n->list($third), fn($x) => $x['kind'] === 'buy_declined'), 'third sees decline');
    ok($n->unseen($buyer) >= 1); $n->markSeen($buyer); eq(0, $n->unseen($buyer));
});

suite('Swap');
test('swap: approve → propose → owner accepts → proposer confirms → complete', function () use ($mkG, $sw, $seller, $buyer, $third, $boss) {
    $mine = $mkG($seller, 'Chambray shirt', 'shirt'); $theirs = $mkG($buyer, 'Black skirt', 'skirt'); $other = $mkG($third, 'Red dress', 'dress');
    $id = $sw->create($seller, $mine, ['disclosure' => 'Good condition, no flaws', 'looking_for' => 'skirt M']);
    throws(fn() => $sw->propose($buyer, $id, $theirs, ''), 'not_available');
    $sw->moderate($boss, $id, true);
    throws(fn() => $sw->propose($buyer, $id, $other, ''), 'not found'); // can only offer own garment
    $sw->propose($buyer, $id, $theirs, 'hi');
    $sw->propose($third, $id, $other, '');
    $rid = Db::get()->value('SELECT id FROM swap_requests WHERE listing_id = ? AND requester_id = ?', [$id, $buyer->id]);
    ok(Policy::canViewGarment(Db::get(), $seller, $theirs), 'owner can see offered garment photo');
    ok(!Policy::canViewGarment(Db::get(), $third, $theirs), 'others cannot');
    throws(fn() => $sw->accept($buyer, $rid), 'not found');
    $sw->accept($seller, $rid);
    ok((bool) array_filter((new NotificationService(Db::get()))->list($buyer), fn($x) => $x['kind'] === 'swap_accepted_by_owner'), 'proposer notified');
    eq('matched', Db::get()->value('SELECT status FROM swap_listings WHERE id = ?', [$id]));
    eq('declined', Db::get()->value('SELECT status FROM swap_requests WHERE listing_id = ? AND requester_id = ?', [$id, $third->id]));
    throws(fn() => $sw->confirm($seller, $rid), 'not found');
    $sw->confirm($buyer, $rid);
    throws(fn() => $sw->complete($third, $rid), 'not found');
    $sw->complete($seller, $rid);
    eq('completed', Db::get()->value('SELECT status FROM swap_requests WHERE id = ?', [$rid]));
});

suite('Favourites, outfits');
test('favourites toggle and validate the target', function () use ($st, $buyer) {
    ok($st->toggleFavorite($buyer, 't:crossbody-bag')); eq(['t:crossbody-bag'], $st->favorites($buyer));
    ok(!$st->toggleFavorite($buyer, 't:crossbody-bag')); eq([], $st->favorites($buyer));
    throws(fn() => $st->toggleFavorite($buyer, "x:'; DROP TABLE users"), 'bad target');
});
test('outfits use only the owner’s garments, 2–8 pieces', function () use ($st, $mkG, $buyer, $seller) {
    $a = $mkG($buyer, 'Top', 'shirt', false); $b = $mkG($buyer, 'Jeans', 'jeans', false); $x = $mkG($seller, 'Not mine', 'jeans', false);
    throws(fn() => $st->saveOutfit($buyer, [$a], 'x'), 'outfit_size');
    throws(fn() => $st->saveOutfit($buyer, [$a, $x], 'x'), 'not found');
    $id = $st->saveOutfit($buyer, [$a, $b], 'Saturday');
    eq(2, count($st->outfits($buyer)[0]['items']));
    $st->deleteOutfit($seller, $id); eq(1, count($st->outfits($buyer)), 'others cannot delete');
    ok(count($st->suggestions((new GarmentService(Db::get()))->listMine($buyer))) >= 1, 'rule-based idea');
});

suite('Payments: settings and slips');
test('only admins edit bank details', function () use ($buyer, $boss) {
    $p = new PaymentService(Db::get());
    throws(fn() => $p->saveSettings($buyer, ['bank' => 'x']), 'admin');
    $p->saveSettings($boss, ['bank' => 'Test Bank', 'account_no' => '123-4-56789-0', 'account_name' => 'MEMORii', 'promptpay' => '', 'note' => '']);
    eq('Test Bank', $p->settings()['bank']);
});
test('customer attaches a slip only to their own order awaiting payment; staff can view it', function () use ($db, $buyer, $third, $boss) {
    $order = Db::uuid(); $req = Db::get()->value('SELECT id FROM design_requests LIMIT 1');
    $cust = Db::get()->value('SELECT customer_id FROM design_requests WHERE id = ?', [$req]);
    $q = Db::get()->value('SELECT id FROM quotations WHERE request_id = ? LIMIT 1', [$req]);
    $existing = Db::get()->one('SELECT * FROM orders LIMIT 1');
    ok($existing !== null, 'an order exists from the workflow suite');
    $p = new PaymentService(Db::get()); $png = tmp_png();
    $owner = App\Core\Actor::load(Db::get(), $existing['customer_id']);
    $file = ['tmp_name' => $png, 'size' => filesize($png), 'error' => 0];
    throws(fn() => $p->addSlip($third, $existing['id'], $file), 'not found');
    if ($existing['status'] !== 'AWAITING_PAYMENT') {
        throws(fn() => $p->addSlip($owner, $existing['id'], $file), 'not_awaiting_payment');
        Db::get()->exec("UPDATE orders SET status = 'AWAITING_PAYMENT' WHERE id = ?", [$existing['id']]);
    }
    $p->addSlip($owner, $existing['id'], $file);
    $sid = $p->slips($existing['id'])[0]['id'];
    ok($p->slipForViewer($owner, $sid)['id'] === $sid);
    ok($p->slipForViewer($boss, $sid)['id'] === $sid);
    throws(fn() => $p->slipForViewer($third, $sid), 'not found');
});
