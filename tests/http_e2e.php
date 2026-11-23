<?php
declare(strict_types=1);
/**
 * HTTP end-to-end test of the vertical slice through the real web stack (router, sessions, CSRF, views, uploads).
 * Usage: start the app (php -S localhost:8000 -t public public/router.php) with MAIL_DRIVER=log, then
 *        php tests/http_e2e.php http://localhost:8000
 * Creates its own uniquely-named users; uses bin/create-admin.php to bootstrap the admin.
 */
require __DIR__ . '/lib.php';
$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$root = dirname(__DIR__);
$mailLog = $root . '/storage/logs/mail.log';

final class Browser
{
    public string $html = '';
    public int $status = 0;
    public ?string $location = null;
    public string $url = '';
    private string $jar;
    public function __construct(private string $base) { $this->jar = tempnam(sys_get_temp_dir(), 'jar'); }

    public function get(string $path, bool $follow = true): self { return $this->req('GET', $path, null, $follow); }
    /** Posts a form; adds the CSRF token scraped from the last page. */
    public function post(string $path, array $fields = [], bool $follow = true): self
    {
        if (!isset($fields['_csrf']) && preg_match('/name="_csrf" value="([0-9a-f]{64})"/', $this->html, $m)) $fields['_csrf'] = $m[1];
        return $this->req('POST', $path, $fields, $follow);
    }
    private function req(string $method, string $path, ?array $fields, bool $follow): self
    {
        for ($hops = 0; $hops < 6; $hops++) {
            $this->url = $path;
            $ch = curl_init($this->base . $path);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20]);
            if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); }
            $raw = (string) curl_exec($ch);
            $this->status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $headers = substr($raw, 0, $hs);
            $this->html = substr($raw, $hs);
            $this->location = preg_match('/^Location:\s*(\S+)/mi', $headers, $m) ? $m[1] : null;
            if (!$follow || !$this->location) return $this;
            $path = $this->location; $method = 'GET'; $fields = null;
        }
        throw new RuntimeException('too many redirects');
    }
    public function sees(string $text): bool { return str_contains(html_entity_decode($this->html, ENT_QUOTES), $text); }
    /** Final URL path after redirects (or the redirect target when not following). */
    public function path(): string { return $this->location ?? $this->url; }
}
function lastToken(string $file, string $route): string
{
    preg_match_all('#' . preg_quote($route, '#') . '\?token=([0-9a-f]{64})#', (string) @file_get_contents($file), $m);
    return (string) end($m[1]);
}
function uuidIn(string $s, string $prefix): string
{
    if (!preg_match('#' . preg_quote($prefix, '#') . '([0-9a-f\-]{36})#', $s, $m)) throw new RuntimeException("no id after {$prefix}");
    return $m[1];
}

// Test-only: reset rate-limit buckets so repeated local runs are not throttled (the limiter itself is exercised below).
require_once $root . '/app/bootstrap.php';
App\Core\Db::get()->exec('DELETE FROM rate_limits');
$run = bin2hex(random_bytes(3));
$cust = new Browser($base); $admin = new Browser($base); $des = new Browser($base); $other = new Browser($base);
$pw = 'password123';
$register = function (Browser $b, string $email, string $name) use ($pw, $mailLog) {
    $b->get('/register');
    $b->post('/register', ['display_name' => $name, 'email' => $email, 'password' => $pw, 'accept_terms' => '1']);
    $b->get('/verify-email?token=' . lastToken($mailLog, '/verify-email'));
};
$login = function (Browser $b, string $email) use ($pw) { $b->get('/login'); $b->post('/login', ['email' => $email, 'password' => $pw, 'accept_terms' => '1']); };

suite('HTTP: public pages & guards');
test('home renders Thai by default with templates from DB', function () use ($cust) {
    $cust->get('/');
    eq(200, $cust->status);
    ok($cust->sees('ให้เสื้อผ้าที่คุณรัก'), 'thai hero');
    ok($cust->sees('กางเกงยีนส์เป็นกระโปรง'), 'template from DB');
});
test('security headers present', function () use ($base) {
    $h = get_headers($base . '/', true);
    ok(isset($h['Content-Security-Policy'], $h['X-Frame-Options'], $h['X-Content-Type-Options']));
});
test('protected pages redirect guests to login', function () use ($cust) {
    $cust->get('/wardrobe', false);
    eq(303, $cust->status); ok(str_starts_with($cust->path(), '/login?next='));
});
test('POST without CSRF token is rejected', function () use ($cust) {
    $cust->post('/register', ['_csrf' => 'x', 'display_name' => 'A', 'email' => 'csrf@test.com', 'password' => 'password123']);
    ok($cust->sees('เซสชันหมดอายุ'), 'csrf message');
});
test('language switch to English', function () use ($other) {
    $other->get('/'); $other->post('/locale', ['locale' => 'en', '_back' => '/']);
    ok($other->sees('Not just clothing'));
});
test('unknown route is 404; path traversal on media is 404', function () use ($other) {
    $other->get('/nope'); eq(404, $other->status);
    $other->get('/media/garment/../../.env'); ok($other->status === 404 || $other->status === 303);
});

suite('HTTP: customer journey');
$ce = "cust{$run}@test.com"; $ae = "admin{$run}@test.com"; $de = "des{$run}@test.com"; $oe = "other{$run}@test.com";
test('register → unverified login refused → verify → login lands on wardrobe', function () use ($cust, $ce, $pw, $mailLog, $login) {
    $cust->get('/register');
    $cust->post('/register', ['display_name' => 'Cust', 'email' => $ce, 'password' => $pw, 'accept_terms' => '1']);
    ok($cust->sees('กรุณายืนยันอีเมล'), 'check email notice');
    $cust->get('/login'); $cust->post('/login', ['email' => $ce, 'password' => $pw, 'accept_terms' => '1']);
    ok($cust->sees('กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ'), 'unverified refused');
    $cust->get('/verify-email?token=' . lastToken($mailLog, '/verify-email'));
    ok($cust->sees('ยืนยันอีเมลแล้ว'));
    $login($cust, $ce);
    ok($cust->sees('ตู้เสื้อผ้าดิจิทัล'), 'wardrobe page');
});
$gid = '';
test('create garment with photo upload; image served only via media route', function () use ($cust, &$gid) {
    $png = tempnam(sys_get_temp_dir(), 'p') . '.png';
    $im = imagecreatetruecolor(20, 30); imagepng($im, $png);
    $cust->get('/wardrobe/new');
    $cust->post('/wardrobe', ['name' => 'E2E Levi', 'category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'worn',
        'defects[0]' => 'hole', 'measurements[waist_cm]' => '76', 'composition_text' => '100% cotton', 'photos[0]' => new CURLFile($png, 'image/png', 'a.png')]);
    ok($cust->sees('E2E Levi') && $cust->sees('อัปโหลดแล้ว 1 รูป'), 'garment page + upload flash');
    $gid = uuidIn($cust->html, '/wardrobe/');
    $img = uuidIn($cust->html, '/media/garment/');
    $cust->get('/media/garment/' . $img); eq(200, $cust->status); ok(str_starts_with($cust->html, "\x89PNG"), 'png bytes');
});
test('another customer gets 404 for the garment and its image (IDOR)', function () use ($other, $cust, $oe, $register, $login, &$gid) {
    $register($other, $oe, 'Other'); $login($other, $oe);
    $other->get('/wardrobe', false); eq(200, $other->status, 'other is logged in');
    $other->get('/wardrobe/' . $gid, false); eq(404, $other->status);
    $cust->get('/wardrobe/' . $gid); $img = uuidIn($cust->html, '/media/garment/');
    $other->get('/media/garment/' . $img); eq(404, $other->status);
});
test('assessment form → result page with 7 pathways and rule ids', function () use ($cust, &$gid) {
    $cust->get("/wardrobe/{$gid}/assess");
    $cust->post("/wardrobe/{$gid}/assess", ['desiredOutcome' => 'new_look', 'budgetMaxThb' => '', 'hasExtraMaterial' => '1']);
    ok($cust->sees('ผลการประเมิน') && $cust->sees('R-REDESIGN-1') && substr_count($cust->html, 'class="card"') >= 7);
});
$rid = '';
test('studio: template request needs acknowledgement, then submits', function () use ($cust, &$gid, &$rid) {
    $cust->get('/studio?garment=' . $gid);
    $tpl = uuidIn($cust->html, 'template=');
    $cust->get("/studio?garment={$gid}&template={$tpl}&mode=template");
    $cust->post('/studio', ['garment_id' => $gid, 'template_id' => $tpl, 'mode' => 'template', 'title' => 'E2E skirt', 'requirements' => 'knee', 'submit' => '1']);
    ok($cust->sees('กรุณายืนยันว่าเข้าใจเงื่อนไข'), 'ack required');
    $cust->post('/studio', ['garment_id' => $gid, 'template_id' => $tpl, 'mode' => 'template', 'title' => 'E2E skirt', 'requirements' => 'knee', 'acknowledge' => '1', 'submit' => '1']);
    ok($cust->sees('ส่งแล้ว'), 'submitted badge');
    $rid = uuidIn($cust->path() . ' ' . $cust->html, '/requests/');
});

suite('HTTP: admin → designer → customer → order');
test('admin bootstrap, designer role grant, staff pages forbidden to customers', function () use ($admin, $des, $cust, $ae, $de, $register, $login, $root) {
    $register($admin, $ae, 'Admin'); $register($des, $de, 'Designer' . $GLOBALS['run']);
    exec('php ' . escapeshellarg($root . '/bin/create-admin.php') . ' ' . escapeshellarg($ae), $o, $code); eq(0, $code);
    $login($admin, $ae);
    ok($admin->sees('แดชบอร์ดแอดมิน'), 'admin lands on dashboard');
    $cust->get('/admin'); eq(403, $cust->status);
    $admin->get('/admin/users');
    preg_match('#<strong>Designer' . $GLOBALS['run'] . '</strong>.*?<code class="muted">([0-9a-f\-]{36})</code>#s', $admin->html, $m);
    ok(!empty($m[1]), 'designer listed');
    $admin->post('/admin/users/grant', ['user_id' => $m[1], 'role' => 'designer', 'public_name' => 'E2E Studio ' . $GLOBALS['run']]);
    ok($admin->sees('มอบบทบาทแล้ว'));
});
test('admin reviews and assigns designer', function () use ($admin, &$rid) {
    $admin->get("/admin/requests/{$rid}");
    $admin->post("/requests/{$rid}/transition", ['to' => 'UNDER_REVIEW', 'note' => 'ok', '_back' => "/admin/requests/{$rid}"]);
    preg_match('#<option value="([0-9a-f\-]{36})"[^>]*>E2E Studio ' . $GLOBALS['run'] . '</option>#', $admin->html, $m);
    ok(!empty($m[1]), 'designer option');
    $admin->post("/admin/requests/{$rid}/assign", ['designer_id' => $m[1]]);
    ok($admin->sees('กำลังจัดทำใบเสนอราคา'));
});
test('customer cannot open the designer portal; designer submits proposal', function () use ($cust, $des, $de, $login, &$rid) {
    $cust->get("/designer/requests/{$rid}"); eq(403, $cust->status);
    $login($des, $de);
    $des->get("/designer/requests/{$rid}");
    ok($des->sees('เสนอแบบและใบเสนอราคา'), "designer page ({$des->status}): " . substr(strip_tags($des->html), 0, 300));
    $des->post("/designer/requests/{$rid}/proposal", ['description' => 'A-line knee skirt from both legs', 'difficulty' => 'medium', 'valid_days' => '14',
        'materials' => 'denim panel', 'feasibility_notes' => 'hole removed',
        'items[0][kind]' => 'design_fee', 'items[0][description]' => 'Design', 'items[0][quantity]' => '1', 'items[0][unit_price_thb]' => '1000',
        'items[1][kind]' => 'labor', 'items[1][description]' => 'Sewing', 'items[1][quantity]' => '1', 'items[1][unit_price_thb]' => '500',
        'items[2][kind]' => 'other', 'items[2][description]' => '', 'items[2][quantity]' => '1', 'items[2][unit_price_thb]' => '']);
    ok($des->sees('ส่งใบเสนอราคาแล้ว') && $des->sees('฿1,605.00'), 'quote total 1500 + VAT');
});
$oid = '';
test('customer sees quotation, accepts, lands on order awaiting payment', function () use ($cust, &$rid, &$oid) {
    $cust->get("/requests/{$rid}");
    ok($cust->sees('฿1,605.00') && $cust->sees('อนุมัติใบเสนอราคา'));
    $q = uuidIn($cust->html, '/quotations/');
    $cust->post("/quotations/{$q}/respond", ['decision' => 'accept', '_back' => "/requests/{$rid}"]);
    ok($cust->sees('รอชำระเงิน'), 'order page');
    $oid = uuidIn($cust->path(), '/orders/');
});
test('admin records transfer → payment confirmed; customer sees progress', function () use ($admin, $cust, &$oid) {
    $admin->get("/admin/orders/{$oid}");
    $admin->post("/admin/orders/{$oid}/payment", ['amount' => '1605.00', 'reference' => 'KB-' . bin2hex(random_bytes(3))]);
    ok($admin->sees('บันทึกการรับโอนแล้ว') && $admin->sees('ยืนยันการชำระเงิน'));
    $cust->get("/orders/{$oid}");
    ok((bool) preg_match('#<li class="now"><i>\d+</i><span>ยืนยันการชำระเงิน#u', $cust->html), 'milestone highlighted');
});
test('customer cannot record payments or move production', function () use ($cust, &$oid) {
    $cust->get("/orders/{$oid}");
    $cust->post("/admin/orders/{$oid}/payment", ['amount' => '1', 'reference' => 'hack'], false);
    eq(403, $cust->status);
    $cust->get("/orders/{$oid}");
    $cust->post("/orders/{$oid}/transition", ['to' => 'SHIPPED', '_back' => "/orders/{$oid}"]);
    ok($cust->sees('not allowed'));
});
test('every main page renders 200 for its role', function () use ($cust, $admin, $des, &$gid, &$rid, &$oid) {
    foreach (['/wardrobe', "/wardrobe/{$gid}", "/wardrobe/{$gid}/edit", '/wardrobe/new', '/studio', '/studio?mode=custom&garment=' . $gid, '/studio?mode=ai', '/requests', "/requests/{$rid}", "/orders/{$oid}", '/account'] as $p) {
        $cust->get($p); eq(200, $cust->status, $p);
    }
    foreach (['/admin', '/admin/requests', '/admin/requests?status=QUOTE_ACCEPTED', "/admin/requests/{$rid}", '/admin/orders', "/admin/orders/{$oid}", '/admin/users', '/admin/audit'] as $p) {
        $admin->get($p); eq(200, $admin->status, $p);
    }
    foreach (['/designer', "/designer/requests/{$rid}"] as $p) { $des->get($p); eq(200, $des->status, $p); }
    foreach (['/', '/studio?kind=bag', '/market', '/swap', '/style', '/saved', '/inbox', '/learn', '/privacy', '/terms', '/contact', "/wardrobe/{$gid}/sell", "/wardrobe/{$gid}/swap"] as $p) {
        $cust->get($p); eq(200, $cust->status, $p);
    }
    foreach (['/admin/moderation', '/admin/settings'] as $p) { $admin->get($p); eq(200, $admin->status, $p); }
});
test('guests can browse home, studio and learn; members-only pages redirect to login', function () use ($base) {
    $g = new Browser($base);
    foreach (['/', '/studio', '/studio?kind=home', '/learn', '/privacy', '/terms'] as $p) { $g->get($p); eq(200, $g->status, $p); }
    $g->get('/studio?kind=bag'); ok($g->sees('กระเป๋าสะพายข้างจากยีนส์') && !$g->sees('เดรสยีนส์'), 'bag templates listed');
    foreach (['/market', '/swap', '/inbox', '/admin/settings'] as $p) { $g->get($p, false); eq(303, $g->status, $p); ok(str_starts_with((string) $g->location, '/login'), $p); }
});
test('Rewear over HTTP: list with photo → admin approves → another member requests → seller accepts', function () use ($cust, $admin, $other, &$gid) {
    $png = tempnam(sys_get_temp_dir(), 'p') . '.png'; imagepng(imagecreatetruecolor(20, 30), $png);
    $cust->get('/wardrobe/new');
    $cust->post('/wardrobe', ['name' => 'Sell Jacket', 'category' => 'denim_jacket', 'fabric_primary' => 'denim', 'condition' => 'good', 'photos[0]' => new CURLFile($png, 'image/png', 'a.png')]);
    $g2 = uuidIn($cust->html, '/wardrobe/');
    $cust->get("/wardrobe/{$g2}/sell");
    $cust->post("/wardrobe/{$g2}/sell", ['title' => 'Sell Jacket', 'price' => '450', 'size' => 'M', 'disclosure' => 'Good condition, no visible flaws']);
    ok($cust->sees('รอตรวจ'), 'pending');
    $lid = uuidIn($cust->url . ' ' . $cust->html, '/market/');
    $other->get("/market/{$lid}", false); eq(404, $other->status);
    $admin->get('/admin/moderation'); ok($admin->sees('Sell Jacket'), 'in moderation queue');
    $admin->post('/admin/moderate', ['kind' => 'market', 'id' => $lid, 'decision' => 'approve', '_back' => '/admin/moderation']);
    $other->get('/market'); ok($other->sees('Sell Jacket'), 'live');
    $other->get("/market/{$lid}"); $other->post("/market/{$lid}/buy", ['message' => 'interested']);
    ok($other->sees('waiting for the seller'), 'buy request pending (EN locale)');
    $cust->get("/market/{$lid}"); ok($cust->sees('interested'), 'seller sees request');
    $oid = uuidIn($cust->html, '/market-offers/');
    $cust->post("/market-offers/{$oid}/decide", ['decision' => 'accept']);
    ok($cust->sees('จองแล้ว'), 'reserved');
    $other->get('/inbox'); ok($other->sees('seller accepted'), 'buyer notified');
});
test('registration is rate limited per IP', function () use ($other) {
    $other->get('/register');
    for ($i = 0; $i < 6; $i++) $other->post('/register', ['display_name' => 'x', 'email' => "spam{$i}" . bin2hex(random_bytes(2)) . '@test.com', 'password' => 'password123']);
    ok($other->sees('Too many attempts'), 'throttled');
});
test('logout ends the session', function () use ($cust) {
    $cust->get('/account'); $cust->post('/logout');
    $cust->get('/wardrobe', false); eq(303, $cust->status);
});

$t = $GLOBALS['__t'];
printf("\n%d passed, %d failed\n", $t['pass'], $t['fail']);
exit($t['fail'] ? 1 : 0);
