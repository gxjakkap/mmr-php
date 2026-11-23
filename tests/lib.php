<?php
declare(strict_types=1);
// Minimal zero-dependency test harness (works on hosts without Composer).

$GLOBALS['__t'] = ['pass' => 0, 'fail' => 0, 'failures' => []];

function test(string $name, callable $fn): void
{
    try { $fn(); $GLOBALS['__t']['pass']++; echo "  ✓ {$name}\n"; }
    catch (Throwable $e) {
        $GLOBALS['__t']['fail']++;
        $GLOBALS['__t']['failures'][] = $name;
        echo "  ✗ {$name}\n      " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }
}
function ok(bool $cond, string $msg = 'expected true'): void { if (!$cond) throw new RuntimeException($msg); }
function eq(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) throw new RuntimeException(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}
/** Asserts $fn throws; optionally that the message contains $contains. Returns the message. */
function throws(callable $fn, string $contains = ''): string
{
    try { $fn(); } catch (Throwable $e) {
        if ($contains !== '' && !str_contains($e->getMessage(), $contains)) throw new RuntimeException("threw '{$e->getMessage()}', expected to contain '{$contains}'");
        return $e->getMessage();
    }
    throw new RuntimeException('expected an exception');
}
function suite(string $name): void { echo "\n{$name}\n"; }
