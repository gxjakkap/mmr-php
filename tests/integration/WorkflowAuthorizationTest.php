<?php
declare(strict_types=1);
// End-to-end vertical slice + authorization rules, exercised through the service layer against real MySQL/MariaDB.
use App\Core\{Actor, Db, DomainError};
use App\Services\{AuthService, GarmentService, AssessmentService, RequestService, OrderService, UserService};

$db = Db::get();
$auth = new AuthService($db);
$garments = new GarmentService($db);
$assess = new AssessmentService($db);
$requests = new RequestService($db);
$orders = new OrderService($db);
$users = new UserService($db);

suite('Auth');
$aliceId = '';
test('register creates unverified customer and sends verification mail', function () use ($auth, $db, &$aliceId) {
    @unlink(BASE_PATH . '/storage/logs/mail.log');
    $aliceId = $auth->register('Alice@Test.com', 'password123', 'Alice');
    eq('customer', $db->value('SELECT role FROM user_roles WHERE user_id = ?', [$aliceId]));
    ok($db->value('SELECT email_verified_at FROM users WHERE id = ?', [$aliceId]) === null, 'unverified');
    ok(str_contains((string) file_get_contents(BASE_PATH . '/storage/logs/mail.log'), '/verify-email?token='), 'mail logged');
});
test('passwords are hashed, never stored in plain text', function () use ($db, &$aliceId) {
    $h = $db->value('SELECT password_hash FROM users WHERE id = ?', [$aliceId]);
    ok($h !== 'password123' && password_verify('password123', $h));
});
test('login refused before email verification', function () use ($auth) {
    throws(fn() => $auth->attempt('alice@test.com', 'password123'), 'auth_unverified');
});
test('verification token works once', function () use ($auth) {
    preg_match_all('/token=([0-9a-f]{64})/', file_get_contents(BASE_PATH . '/storage/logs/mail.log'), $m);
    $token = end($m[1]);
    ok($auth->verifyEmail($token));
    ok(!$auth->verifyEmail($token), 'reuse rejected');
});
test('login: wrong password and unknown email give the same error', function () use ($auth, &$aliceId) {
    eq($aliceId, $auth->attempt('ALICE@test.com', 'password123'));
    $a = throws(fn() => $auth->attempt('alice@test.com', 'wrong-pass'));
    $b = throws(fn() => $auth->attempt('nobody@test.com', 'password123'));
    eq($a, $b);
});
test('duplicate registration does not reveal the account exists', function () use ($auth) {
    eq('', $auth->register('alice@test.com', 'password123', 'Again'));
});
test('password reset token resets once', function () use ($auth) {
    $auth->requestReset('alice@test.com');
    preg_match_all('#reset-password\?token=([0-9a-f]{64})#', file_get_contents(BASE_PATH . '/storage/logs/mail.log'), $m);
    $t = end($m[1]);
    ok($auth->tokenIsValid($t, 'reset_password'));
    $auth->resetPassword($t, 'newpassword9');
    throws(fn() => $auth->resetPassword($t, 'another999'), 'link_invalid');
    $auth->attempt('alice@test.com', 'newpassword9');
});
test('reset for unknown email is silent', function () use ($auth) { $auth->requestReset('ghost@test.com'); ok(true); });

$alice = Actor::load($db, $aliceId);
$bob = make_user('bob@test.com');
$ada = make_user('ada@test.com', ['admin']);
$dee = make_user('dee@test.com');
$tom = make_user('tom@test.com');

suite('Roles');
test('customer cannot grant roles', function () use ($users, $alice) {
    throws(fn() => $users->grantRole($alice, $alice->id, 'admin'), 'admin only');
});
test('admin grants designer role and creates designer record', function () use ($users, $ada, $dee, $db) {
    $users->grantRole($ada, $dee->id, 'designer', 'Dee Studio');
    eq('Dee Studio', $db->value('SELECT public_name FROM designers WHERE user_id = ?', [$dee->id]));
});
$dee = Actor::load($db, $dee->id);

suite('Wardrobe isolation & uploads');
$g1 = '';
test('alice creates a garment', function () use ($garments, $alice, &$g1) {
    $g1 = $garments->create($alice, ['name' => "Old Levi's", 'category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'worn',
        'defects' => ['hole', 'worn_hem'], 'composition_text' => '98% cotton, 2% elastane', 'measurements' => ['waist_cm' => '76', 'length_cm' => '']]);
    eq(1, count($garments->listMine($alice)));
});
test('invalid enum values are rejected', function () use ($garments, $alice) {
    throws(fn() => $garments->create($alice, ['name' => 'x', 'category' => 'socks', 'fabric_primary' => 'denim', 'condition' => 'good']), 'category');
    throws(fn() => $garments->create($alice, ['name' => 'x', 'category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'good', 'defects' => ['laser']]), 'defect');
});
test('bob cannot see, edit, delete or upload to alice garment (IDOR)', function () use ($garments, $bob, &$g1) {
    eq(0, count($garments->listMine($bob)));
    throws(fn() => $garments->getOwned($bob, $g1), 'not found');
    throws(fn() => $garments->update($bob, $g1, ['name' => 'hacked', 'category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'good']), 'not found');
    throws(fn() => $garments->delete($bob, $g1), 'not found');
    throws(fn() => $garments->addImages($bob, $g1, [['tmp_name' => tmp_png(), 'size' => 100, 'error' => 0]]), 'not found');
});
$img = '';
test('valid PNG upload stored outside web root; fake image rejected', function () use ($garments, $alice, &$g1, &$img, $db) {
    $png = tmp_png();
    eq(1, $garments->addImages($alice, $g1, [['tmp_name' => $png, 'size' => filesize($png), 'error' => 0]]));
    $img = $db->value('SELECT id FROM garment_images WHERE garment_id = ?', [$g1]);
    $fake = tempnam(sys_get_temp_dir(), 'x'); file_put_contents($fake, '<?php echo 1;');
    throws(fn() => $garments->addImages($alice, $g1, [['tmp_name' => $fake, 'size' => 14, 'error' => 0]]), 'photo_bad_type');
    throws(fn() => $garments->addImages($alice, $g1, [['tmp_name' => $png, 'size' => 9_000_000, 'error' => 0]]), 'photo_too_big');
});
test('image access: owner yes, bob no, unassigned designer no', function () use ($garments, $alice, $bob, $dee, &$img) {
    ok((bool) $garments->imageForViewer($alice, $img));
    throws(fn() => $garments->imageForViewer($bob, $img), 'not found');
    throws(fn() => $garments->imageForViewer($dee, $img), 'not found');
});

suite('Assessment');
$as = '';
test('assessment stored with inputs, engine version and 7 ranked pathways', function () use ($assess, $alice, &$g1, &$as) {
    $as = $assess->run($alice, $g1, ['desiredOutcome' => 'new_look', 'budgetMaxThb' => '', 'hasExtraMaterial' => 'on']);
    $r = $assess->get($alice, $as);
    eq('rules-1.0.0', $r['engine_version']);
    eq(7, count($r['recommendations']));
    eq(1, (int) $r['is_preliminary']);
});
test('bob cannot read alice assessment', function () use ($assess, $bob, &$as) { throws(fn() => $assess->get($bob, $as), 'not found'); });

suite('Design request lifecycle');
$req = '';
$tpl = $db->value("SELECT id FROM design_templates WHERE slug = 'jeans-to-skirt'");
test('feasibility acknowledgement is required', function () use ($requests, $alice, &$g1, $tpl) {
    throws(fn() => $requests->create($alice, ['garment_id' => $g1, 'mode' => 'template', 'template_id' => $tpl, 'title' => 'x']), 'acknowledge_required');
});
test('bob cannot create a request on alice garment', function () use ($requests, $bob, &$g1, $tpl) {
    throws(fn() => $requests->create($bob, ['garment_id' => $g1, 'mode' => 'template', 'template_id' => $tpl, 'title' => 'steal', 'acknowledge' => 1]), 'not found');
});
test('alice creates and submits a request; conversation opened', function () use ($requests, $alice, &$g1, $tpl, &$req, $db) {
    $req = $requests->create($alice, ['garment_id' => $g1, 'mode' => 'template', 'template_id' => $tpl, 'title' => 'Skirt please',
        'requirements' => 'A-line, knee', 'acknowledge' => 'on', 'submit' => '1']);
    eq('SUBMITTED', $db->value('SELECT status FROM design_requests WHERE id = ?', [$req]));
    ok((bool) $db->value('SELECT id FROM conversations WHERE design_request_id = ?', [$req]));
});
test('garment with a request cannot be deleted', function () use ($garments, $alice, &$g1) { throws(fn() => $garments->delete($alice, $g1), 'delete_blocked'); });
test('customer cannot move to UNDER_REVIEW; bob cannot see it at all', function () use ($requests, $alice, $bob, &$req) {
    throws(fn() => $requests->transition($alice, $req, 'UNDER_REVIEW'), 'not allowed');
    throws(fn() => $requests->detail($bob, $req), 'not found');
    throws(fn() => $requests->transition($bob, $req, 'CANCELLED'), 'not found');
});
test('unassigned designer cannot see request or garment', function () use ($requests, $dee, &$req) {
    throws(fn() => $requests->detail($dee, $req), 'not found');
    eq([], $requests->listForDesigner($dee));
});
test('admin reviews; system edges are not reachable generically', function () use ($requests, $ada, &$req) {
    $requests->transition($ada, $req, 'UNDER_REVIEW', 'looks feasible');
    throws(fn() => $requests->transition($ada, $req, 'QUOTE_SENT'), 'not allowed');
});
test('only admin assigns; assignment moves to QUOTE_PREPARING', function () use ($requests, $ada, $alice, &$req, $db) {
    $did = $db->value('SELECT id FROM designers LIMIT 1');
    throws(fn() => $requests->assignDesigner($alice, $req, $did), 'admin only');
    $requests->assignDesigner($ada, $req, $did);
    eq('QUOTE_PREPARING', $db->value('SELECT status FROM design_requests WHERE id = ?', [$req]));
});
test('assigned designer now sees request + garment image, not customer email/phone', function () use ($requests, $garments, $dee, &$req, &$img) {
    $d = $requests->detail($dee, $req);
    ok(!isset($d['request']['email']) && !array_key_exists('phone', $d['garment'] ?? []));
    ok((bool) $garments->imageForViewer($dee, $img));
});

$q1 = '';
test('designer proposal: server-computed total 2,033.00 incl. VAT', function () use ($requests, $dee, &$req, &$q1, $db) {
    $q1 = $requests->submitProposal($dee, $req, ['description' => 'A-line skirt using both legs', 'difficulty' => 'medium',
        'materials' => 'denim panel', 'feasibility_notes' => 'Hole near knee will be cut away', 'valid_days' => '14', 'items' => [
            ['kind' => 'design_fee', 'description' => 'Design', 'quantity' => '1', 'unit_price_thb' => '1000'],
            ['kind' => 'labor', 'description' => 'Sewing', 'quantity' => '2', 'unit_price_thb' => '500'],
            ['kind' => 'discount', 'description' => 'Launch', 'quantity' => '1', 'unit_price_thb' => '100'],
            ['kind' => 'other', 'description' => '', 'quantity' => '1', 'unit_price_thb' => ''], // blank row ignored
        ]]);
    eq(203300, (int) $db->value('SELECT total_satang FROM quotations WHERE id = ?', [$q1]));
    eq(3, (int) $db->value('SELECT COUNT(*) FROM quotation_items WHERE quotation_id = ?', [$q1]));
});
test('non-assigned users cannot submit proposals; no second quote while one is pending', function () use ($requests, $ada, $dee, &$req) {
    $items = ['description' => 'xxxxxxxxxxxx', 'difficulty' => 'low', 'valid_days' => 7, 'items' => [['kind' => 'labor', 'description' => 'x', 'quantity' => 1, 'unit_price_thb' => '1']]];
    throws(fn() => $requests->submitProposal($ada, $req, $items), 'assigned designer');
    throws(fn() => $requests->submitProposal($dee, $req, $items), 'not awaiting');
});
test('quotation amounts are immutable at the database level', function () use ($db, &$q1) {
    throws(fn() => $db->exec('UPDATE quotations SET total_satang = 1, subtotal_satang = 1 WHERE id = ?', [$q1]), 'immutable');
});
test('bob cannot accept alice quotation', function () use ($requests, $bob, &$q1) {
    throws(fn() => $requests->respondQuotation($bob, $q1, true), 'not found');
});
$order = '';
test('alice accepts → order AWAITING_PAYMENT with copied items', function () use ($requests, $alice, &$q1, &$order, $db) {
    $order = $requests->respondQuotation($alice, $q1, true);
    eq('AWAITING_PAYMENT', $db->value('SELECT status FROM orders WHERE id = ?', [$order]));
    eq(203300, (int) $db->value('SELECT total_satang FROM orders WHERE id = ?', [$order]));
    eq(3, (int) $db->value('SELECT COUNT(*) FROM order_items WHERE order_id = ?', [$order]));
    throws(fn() => $requests->respondQuotation($alice, $q1, false), 'accepted');
    throws(fn() => $db->exec("UPDATE quotations SET status = 'rejected' WHERE id = ?", [$q1]), 'final');
});

suite('Orders & payments');
test('customer cannot confirm payment; bob cannot see order', function () use ($orders, $alice, $bob, &$order) {
    throws(fn() => $orders->transition($alice, $order, 'PAYMENT_CONFIRMED'), 'not allowed');
    throws(fn() => $orders->recordManualPayment($alice, $order, 203300, 'KBANK-1'), 'admin only');
    throws(fn() => $orders->detail($bob, $order), 'not found');
});
test('partial payment keeps order awaiting; full payment confirms', function () use ($orders, $ada, &$order, $db) {
    $orders->recordManualPayment($ada, $order, 100000, 'KBANK-001');
    eq('AWAITING_PAYMENT', $db->value('SELECT status FROM orders WHERE id = ?', [$order]));
    throws(fn() => $orders->recordManualPayment($ada, $order, 1, 'KBANK-001'), 'already recorded');
    $orders->recordManualPayment($ada, $order, 103300, 'KBANK-002');
    eq('PAYMENT_CONFIRMED', $db->value('SELECT status FROM orders WHERE id = ?', [$order]));
});
test('admin moves order; illegal jumps blocked; history recorded', function () use ($orders, $ada, &$order, $db) {
    $orders->transition($ada, $order, 'AWAITING_GARMENT');
    $orders->transition($ada, $order, 'GARMENT_RECEIVED');
    throws(fn() => $orders->transition($ada, $order, 'SHIPPED'), 'not allowed');
    eq(4, (int) $db->value('SELECT COUNT(*) FROM order_status_history WHERE order_id = ?', [$order]));
});
test('payments / history / audit are append-only', function () use ($db) {
    throws(fn() => $db->exec('UPDATE payments SET amount_satang = 1'), 'append-only');
    throws(fn() => $db->exec('DELETE FROM payments'), 'append-only');
    throws(fn() => $db->exec('DELETE FROM audit_logs'), 'append-only');
    throws(fn() => $db->exec("UPDATE order_status_history SET note = 'x'"), 'append-only');
    ok((int) $db->value('SELECT COUNT(*) FROM audit_logs') >= 10, 'audit trail written');
});

suite('Production partner skill gate');
$partner = Db::uuid();
$db->exec("INSERT INTO user_roles (id, user_id, role) VALUES (?, ?, 'tailor')", [Db::uuid(), $tom->id]);
$db->insert('production_partners', ['id' => $partner, 'user_id' => $tom->id, 'business_name' => 'Tom Tailor', 'contact_phone' => '0812345678', 'is_verified' => 1]);
$db->insert('partner_skills', ['id' => Db::uuid(), 'partner_id' => $partner, 'skill' => 'garment_reconstruction']);
$db->insert('partner_capacity', ['id' => Db::uuid(), 'partner_id' => $partner, 'week_start' => date('Y-m-d', strtotime('monday this week')), 'max_jobs' => 1]);
$tom = Actor::load($db, $tom->id);
test('unverified skill and unapproved skill are refused', function () use ($orders, $ada, &$order, $partner, $db) {
    throws(fn() => $orders->assignPartner($ada, $order, $partner, 'garment_reconstruction'), 'lacks verified skill');
    $db->exec('UPDATE partner_skills SET verified_at = NOW() WHERE partner_id = ?', [$partner]);
    throws(fn() => $orders->assignPartner($ada, $order, $partner, 'complex_dressmaking'), 'lacks verified skill');
});
test('verified skill with capacity is accepted; capacity consumed', function () use ($orders, $ada, &$order, $partner, $db) {
    $orders->assignPartner($ada, $order, $partner, 'garment_reconstruction');
    eq(1, (int) $db->value('SELECT committed_jobs FROM partner_capacity WHERE partner_id = ?', [$partner]));
});
test('assigned tailor sees garment image and can start inspection', function () use ($orders, $garments, $tom, &$order, &$img, $db) {
    ok((bool) $garments->imageForViewer($tom, $img));
    $orders->transition($tom, $order, 'PHYSICAL_INSPECTION');
    eq('PHYSICAL_INSPECTION', $db->value('SELECT status FROM orders WHERE id = ?', [$order]));
});
test('customer order view never contains tailor contact data', function () use ($orders, $alice, &$order) {
    $json = json_encode($orders->detail($alice, $order));
    ok(!str_contains($json, '0812345678') && !str_contains($json, 'Tom Tailor'));
});

suite('Rejection, revision, expiry, messages');
$g2 = $garments->create($bob, ['name' => 'Bob jacket', 'category' => 'denim_jacket', 'fabric_primary' => 'denim', 'condition' => 'fair']);
$req2 = $requests->create($bob, ['garment_id' => $g2, 'mode' => 'custom', 'title' => 'Crop it', 'requirements' => 'Shorter', 'acknowledge' => 1, 'submit' => 1]);
$requests->transition($ada, $req2, 'UNDER_REVIEW');
$requests->assignDesigner($ada, $req2, $db->value('SELECT id FROM designers LIMIT 1'));
$line = fn($p) => ['description' => 'Cropped boxy jacket', 'difficulty' => 'medium', 'valid_days' => 14, 'items' => [['kind' => 'labor', 'description' => 'Crop', 'quantity' => 1, 'unit_price_thb' => $p]]];
$qa = $requests->submitProposal($dee, $req2, $line('800'));
test('rejection creates no order', function () use ($requests, $bob, $qa, $req2, $db) {
    eq(null, $requests->respondQuotation($bob, $qa, false, 'too pricey'));
    eq('QUOTE_REJECTED', $db->value('SELECT status FROM design_requests WHERE id = ?', [$req2]));
    eq(0, (int) $db->value('SELECT COUNT(*) FROM orders WHERE request_id = ?', [$req2]));
});
$qb = '';
test('designer reopens and issues revision 2 linked to revision 1', function () use ($requests, $dee, $req2, $line, $qa, &$qb, $db) {
    $requests->transition($dee, $req2, 'QUOTE_PREPARING', 'revising');
    $qb = $requests->submitProposal($dee, $req2, $line('600'));
    eq(2, (int) $db->value('SELECT revision_no FROM quotations WHERE id = ?', [$qb]));
    eq($qa, $db->value('SELECT supersedes_id FROM quotations WHERE id = ?', [$qb]));
    eq('rejected', $db->value('SELECT status FROM quotations WHERE id = ?', [$qa]));
});
test('expired quotation cannot be accepted', function () use ($requests, $bob, &$qb, $db) {
    $db->exec('UPDATE quotations SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?', [$qb]);
    throws(fn() => $requests->respondQuotation($bob, $qb, true), 'expired');
});
test('messages: customer cannot post internal; others cannot read; internal hidden from customer', function () use ($requests, $bob, $alice, $ada, $req2) {
    throws(fn() => $requests->sendMessage($bob, $req2, 'x', true), 'internal');
    $requests->sendMessage($bob, $req2, 'hello');
    $requests->sendMessage($ada, $req2, 'staff only note', true);
    throws(fn() => $requests->sendMessage($alice, $req2, 'hi'), 'not found');
    $bodies = array_column($requests->detail($bob, $req2)['messages'], 'body');
    eq(['hello'], $bodies);
    eq(2, count($requests->detail($ada, $req2)['messages']));
});
test('admin overview counts come from live rows', function () use ($orders, $ada, $alice) {
    $o = $orders->overview($ada);
    eq(1, $o['activeOrders']);
    eq(2, $o['garments']);
    throws(fn() => $orders->overview($alice), 'admin only');
});
