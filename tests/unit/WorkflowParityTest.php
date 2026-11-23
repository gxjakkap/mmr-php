<?php
declare(strict_types=1);
use App\Domain\Workflow;

suite('Workflow parity (SQL tables are the source of truth)');
$sql = implode("\n", array_map('file_get_contents', glob(BASE_PATH . '/database/migrations/*.sql')));
$block = function (string $name) use ($sql): array {
    if (!preg_match("/-- BEGIN {$name}(.*?)-- END {$name}/s", $sql, $m)) throw new RuntimeException("block {$name} missing");
    preg_match_all("/\('([A-Z_]+)','([A-Z_]+)','([a-z]+)'\)/", $m[1], $rows, PREG_SET_ORDER);
    $out = array_map(fn($r) => "{$r[1]}>{$r[2]}>{$r[3]}", $rows);
    sort($out);
    return $out;
};
$php = function (array $t): array { $o = array_map(fn($r) => implode('>', $r), $t); sort($o); return $o; };

test('request transitions match SQL', fn() => eq($block('request_transitions'), $php(Workflow::REQUEST_TRANSITIONS)));
test('order transitions match SQL', fn() => eq($block('order_transitions'), $php(Workflow::ORDER_TRANSITIONS)));
test('statuses match CHECK constraints', function () use ($sql) {
    foreach ([...Workflow::REQUEST_STATUSES, ...Workflow::ORDER_STATUSES] as $s) ok(str_contains($sql, "'{$s}'"), $s);
});
test('system edges are never offered as buttons', function () {
    ok(!in_array('QUOTE_SENT', Workflow::nextRequest('QUOTE_PREPARING', ['designer', 'admin']), true));
    ok(!in_array('PAYMENT_CONFIRMED', Workflow::nextOrder('AWAITING_PAYMENT', ['admin', 'customer']), true));
});
test('terminal states have no exits; customers cannot advance production', function () {
    eq([], Workflow::nextOrder('COMPLETED', ['admin', 'customer', 'tailor', 'qc']));
    eq([], Workflow::nextRequest('CANCELLED', ['admin', 'customer', 'designer']));
    eq([], Workflow::nextOrder('IN_PRODUCTION', ['customer']));
});
