<?php
declare(strict_types=1);
// Usage: php tests/run.php            (unit + integration; integration needs DB_TEST_* env or defaults)
//        php tests/run.php unit       (no database)
require_once __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/lib.php';

$only = $argv[1] ?? 'all';
foreach (glob(__DIR__ . '/unit/*Test.php') as $f) require $f;
if ($only !== 'unit') {
    require __DIR__ . '/integration/setup.php';
    foreach (glob(__DIR__ . '/integration/*Test.php') as $f) require $f;
}
$t = $GLOBALS['__t'];
printf("\n%d passed, %d failed\n", $t['pass'], $t['fail']);
exit($t['fail'] ? 1 : 0);
