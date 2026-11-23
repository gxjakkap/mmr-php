<?php
declare(strict_types=1);
use App\Domain\AssessmentEngine as E;

suite('AssessmentEngine');
$base = ['category' => 'jeans', 'fabric' => 'denim', 'condition' => 'good', 'defects' => [], 'hasMeasurements' => true,
    'photoCount' => 3, 'desiredOutcome' => 'keep_wearing', 'budgetMaxThb' => null, 'hasExtraMaterial' => false];
$by = fn(array $r, string $p) => array_values(array_filter($r, fn($x) => $x['pathway'] === $p))[0];

test('seven pathways ranked 1..7, deterministic', function () use ($base) {
    $a = E::assess($base);
    $p = array_column($a, 'pathway'); sort($p);
    $exp = E::PATHWAYS; sort($exp);
    eq($exp, $p);
    eq([1, 2, 3, 4, 5, 6, 7], array_column($a, 'rank'));
    eq($a, E::assess($base));
});
test('every recommendation cites at least one rule; no numeric score', function () use ($base) {
    foreach (E::assess($base) as $r) { ok(count($r['rules']) > 0); ok(!array_key_exists('score', $r)); }
});
test('good, no defects: reuse & rewear recommended; repair & recycle not advised', function () use ($base, $by) {
    $a = E::assess($base);
    eq('recommended', $by($a, 'reuse')['suitability']);
    eq('recommended', $by($a, 'rewear')['suitability']);
    eq('not_advised', $by($a, 'recycle')['suitability']);
    eq('not_advised', $by($a, 'repair')['suitability']);
});
test('repairable defects recommend repair with matching materials, block rewear', function () use ($base, $by) {
    $a = E::assess(['condition' => 'fair', 'defects' => ['hole', 'broken_zip']] + $base);
    eq('recommended', $by($a, 'repair')['suitability']);
    ok(in_array('mat.zip', $by($a, 'repair')['materials'], true) && in_array('mat.patch', $by($a, 'repair')['materials'], true));
    eq('not_advised', $by($a, 'rewear')['suitability']);
});
test('damaged with many defects: recycle + upcycle recommended, crotch wear flagged', function () use ($base, $by) {
    $a = E::assess(['condition' => 'damaged', 'defects' => ['hole', 'tear', 'stain', 'crotch_wear']] + $base);
    eq('recommended', $by($a, 'recycle')['suitability']);
    eq('recommended', $by($a, 'upcycle')['suitability']);
    ok(in_array('dis.repair.may_recur', $by($a, 'repair')['disadvantages'], true));
});
test('budget below band minimum downgrades and flags', function () use ($base, $by) {
    $r = $by(E::assess(['condition' => 'worn', 'budgetMaxThb' => 300, 'desiredOutcome' => 'new_look'] + $base), 'redesign');
    eq('possible', $r['suitability']);
    ok(in_array('R-BUDGET-1', $r['rules'], true) && in_array('dis.over_budget', $r['disadvantages'], true));
});
test('no photos / no measurements raise uncertainty', function () use ($base, $by) {
    $a = E::assess(['photoCount' => 0, 'hasMeasurements' => false] + $base);
    eq('medium', $by($a, 'reuse')['uncertainty']);
    eq('high', $by($a, 'redesign')['uncertainty']);
});
test('desired outcome orders ties without changing suitability', function () use ($base, $by) {
    $sell = E::assess(['desiredOutcome' => 'sell'] + $base);
    eq('rewear', $sell[0]['pathway']);
    eq($by(E::assess($base), 'rewear')['suitability'], $by($sell, 'rewear')['suitability']);
});
test('physical confirmation always a next step for production pathways', function () use ($base, $by) {
    $a = E::assess(['condition' => 'worn', 'defects' => ['hole']] + $base);
    foreach (['repair', 'redesign', 'upcycle'] as $p) ok(in_array('next.confirm_physical', $by($a, $p)['nextSteps'], true), $p);
});
test('blends flagged as harder to recycle', function () use ($base, $by) {
    ok(in_array('dis.recycle.blend_hard', $by(E::assess(['fabric' => 'blend', 'condition' => 'worn'] + $base), 'recycle')['disadvantages'], true));
});
