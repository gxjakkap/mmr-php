<?php
declare(strict_types=1);
// Grants the admin role to an existing (registered) user. Run on the server: php bin/create-admin.php you@example.com
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\{Audit, Db};

$email = strtolower(trim($argv[1] ?? ''));
if ($email === '') { fwrite(STDERR, "usage: php bin/create-admin.php <email>\n"); exit(1); }
$db = Db::get();
$u = $db->one('SELECT id FROM users WHERE email = ?', [$email]);
if (!$u) { fwrite(STDERR, "No user {$email}. Register in the app first.\n"); exit(1); }
$db->exec("INSERT IGNORE INTO user_roles (id, user_id, role) VALUES (?, ?, 'admin')", [Db::uuid(), $u['id']]);
$db->exec('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', [$u['id']]);
Audit::log($db, null, 'role.bootstrap_admin', 'user', $u['id']);
echo "admin granted to {$email} ({$u['id']})\n";
