<?php
declare(strict_types=1);
// Creates a throwaway database and points the app at it. NEVER point DB_TEST_NAME at real data.
use App\Core\{Db, Env, Actor};

$name = getenv('DB_TEST_NAME') ?: 'memorii_test';
if (!preg_match('/_test$/', $name)) { fwrite(STDERR, "DB_TEST_NAME must end with _test\n"); exit(2); }
Env::set('DB_HOST', getenv('DB_TEST_HOST') ?: '127.0.0.1');
Env::set('DB_USER', getenv('DB_TEST_USER') ?: 'memorii');
Env::set('DB_PASS', getenv('DB_TEST_PASS') ?: 'memorii');
Env::set('DB_NAME', 'mysql');
Env::set('UPLOAD_DIR', sys_get_temp_dir() . '/memorii_test_uploads');
Env::set('MAIL_DRIVER', 'log');
Db::reset();
$root = Db::get()->mysqli();
$root->query("DROP DATABASE IF EXISTS `{$name}`");
$root->query("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
Env::set('DB_NAME', $name);
Db::reset();
require_once BASE_PATH . '/bin/migrate.php';
memorii_migrate(Db::get(), true);

/** Test helper: create a verified user with roles and return an Actor. */
function make_user(string $email, array $roles = []): Actor
{
    $db = Db::get();
    $id = (new App\Services\AuthService($db))->register($email, 'password123', ucfirst(strtok($email, '@')));
    $db->exec('UPDATE users SET email_verified_at = NOW() WHERE id = ?', [$id]);
    foreach ($roles as $r) $db->exec('INSERT INTO user_roles (id, user_id, role) VALUES (?, ?, ?)', [Db::uuid(), $id, $r]);
    return Actor::load($db, $id);
}
/** Writes a tiny real PNG to a temp file (for upload tests). */
function tmp_png(): string
{
    $p = tempnam(sys_get_temp_dir(), 'png');
    $im = imagecreatetruecolor(4, 4);
    imagepng($im, $p);
    return $p;
}
