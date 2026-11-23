<?php
declare(strict_types=1);
use App\Domain\{AssessmentEngine, Workflow, Money};
use App\Services\Validate;

suite('I18n & validation');
$th = require BASE_PATH . '/app/Lang/th.php';
$en = require BASE_PATH . '/app/Lang/en.php';
$keys = function (array $a, string $p = '') use (&$keys): array {
    $o = [];
    foreach ($a as $k => $v) $o = array_merge($o, is_array($v) ? $keys($v, "{$p}{$k}.") : ["{$p}{$k}"]);
    sort($o);
    return $o;
};
test('Thai and English have identical keys', fn() => eq($keys($th), $keys($en)));
test('no empty strings', function () use ($th, $en, $keys) {
    foreach ([$th, $en] as $d) array_walk_recursive($d, fn($v, $k) => ok(is_string($v) && $v !== '', (string) $k));
});
test('every status, pathway and quote kind is translated', function () use ($th, $en) {
    foreach (Workflow::REQUEST_STATUSES as $s) ok(isset($th['reqStatus'][$s], $en['reqStatus'][$s]), $s);
    foreach (Workflow::ORDER_STATUSES as $s) ok(isset($th['orderStatus'][$s], $en['orderStatus'][$s]), $s);
    foreach (AssessmentEngine::PATHWAYS as $p) ok(isset($th['pathway'][$p], $en['pathway'][$p]), $p);
    foreach (Money::KINDS as $k) ok(isset($th['quoteKind'][$k], $en['quoteKind'][$k]), $k);
});
test('every text key the engine can emit is translated', function () use ($th, $en) {
    $seen = [];
    foreach (AssessmentEngine::CONDITIONS as $c) foreach (AssessmentEngine::OUTCOMES as $o) foreach ([[], ['hole', 'broken_zip', 'crotch_wear', 'tear'], ['fading']] as $d) foreach (['denim', 'blend', 'leather'] as $f)
        foreach (AssessmentEngine::assess(['category' => 'jeans', 'fabric' => $f, 'condition' => $c, 'defects' => $d, 'hasMeasurements' => false, 'photoCount' => 0, 'desiredOutcome' => $o, 'budgetMaxThb' => 100, 'hasExtraMaterial' => false]) as $r)
            foreach ([...$r['advantages'], ...$r['disadvantages'], ...$r['materials'], ...$r['nextSteps']] as $k) $seen[$k] = true;
    foreach (array_keys($seen) as $k) ok(isset($th['rec'][$k], $en['rec'][$k]), $k);
});
test('garment validation normalises and rejects bad input', function () {
    $g = Validate::garment(['name' => '  Levi ', 'category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'good',
        'measurements' => ['waist_cm' => '', 'length_cm' => '102'], 'composition_text' => '98% Cotton, 2% elastane; junk']);
    eq('Levi', $g['name']);
    eq('{"length_cm":102}', $g['measurements']);
    eq('[{"fiber":"cotton","pct":98},{"fiber":"elastane","pct":2}]', $g['fabric_composition']);
    throws(fn() => Validate::garment(['name' => 'x', 'category' => 'jeans', 'fabric_primary' => 'denim', 'condition' => 'good', 'measurements' => ['waist_cm' => '999']]), 'waist');
    throws(fn() => Validate::uuid('../etc/passwd'));
    throws(fn() => Validate::email('not-an-email'));
});
