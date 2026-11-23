<?php
declare(strict_types=1);
use App\Domain\Money;

suite('Money');
test('fixture total 2,033.00 (VAT 7% on subtotal − discount)', function () {
    $r = Money::totals([
        ['kind' => 'design_fee', 'quantity' => 1, 'unit_price_satang' => 100000],
        ['kind' => 'labor', 'quantity' => 2, 'unit_price_satang' => 50000],
        ['kind' => 'discount', 'quantity' => 1, 'unit_price_satang' => 10000],
    ]);
    eq([200000, 10000, 13300, 203300, true], [$r['subtotal'], $r['discount'], $r['tax'], $r['total'], $r['valid']]);
});
test('discount larger than subtotal is invalid', function () {
    ok(!Money::totals([['kind' => 'discount', 'quantity' => 1, 'unit_price_satang' => 5]])['valid']);
});
test('tax rounds half-up to the satang', function () {
    eq(11, Money::totals([['kind' => 'labor', 'quantity' => 1, 'unit_price_satang' => 150]])['tax']); // 10.5 -> 11
    eq(10, Money::totals([['kind' => 'labor', 'quantity' => 1, 'unit_price_satang' => 149]])['tax']); // 10.43 -> 10
});
test('THB → satang without float drift; rejects junk', function () {
    eq(1999, Money::thbToSatang('19.99'));
    eq(1605, Money::thbToSatang('16.05'));
    eq(100000, Money::thbToSatang('1000'));
    throws(fn() => Money::thbToSatang('1e5'));
    throws(fn() => Money::thbToSatang('-5'));
});
test('formatting', function () {
    eq('฿2,033.00', Money::format(203300, 'th'));
    eq('THB 2,033.00', Money::format(203300, 'en'));
});
