<?php
declare(strict_types=1);
// Usage: php bin/migrate.php        — applies pending database/migrations/*.sql in order
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

function memorii_migrate(Db $db, bool $quiet = false): int
{
    $db->mysqli()->query('CREATE TABLE IF NOT EXISTS schema_migrations (filename VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
    $done = array_column($db->all('SELECT filename FROM schema_migrations'), 'filename');
    $files = glob(BASE_PATH . '/database/migrations/*.sql');
    sort($files);
    $n = 0;
    foreach ($files as $f) {
        $name = basename($f);
        if (in_array($name, $done, true)) continue;
        $db->runFile($f); // DDL auto-commits in MySQL; files are written to be re-run safely only on a fresh DB
        $db->exec('INSERT INTO schema_migrations (filename) VALUES (?)', [$name]);
        if (!$quiet) echo "applied {$name}\n";
        $n++;
    }
    return $n;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    $n = memorii_migrate(Db::get());
    echo $n ? "{$n} migration(s) applied\n" : "nothing to migrate\n";
}
