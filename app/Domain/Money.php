<?php
declare(strict_types=1);
namespace App\Domain;

/** Integer satang arithmetic. Authoritative quotation totals are computed here, on the server. */
final class Money
{
    public const VAT_BP = 700;
    public const KINDS = ['design_fee', 'labor', 'material', 'shipping', 'platform_fee', 'discount', 'other'];

    /** @param array<int, array{kind:string, quantity:float|int|string, unit_price_satang:int}> $lines */
    public static function totals(array $lines, int $taxRateBp = self::VAT_BP): array
    {
        $sub = 0; $disc = 0; $lineTotals = [];
        foreach ($lines as $l) {
            $line = self::roundHalfUp((float) $l['quantity'] * (int) $l['unit_price_satang']);
            $lineTotals[] = $line;
            if ($l['kind'] === 'discount') $disc += $line; else $sub += $line;
        }
        $taxable = max($sub - $disc, 0);
        $tax = intdiv($taxable * $taxRateBp * 2 + 10000, 20000); // half-up of taxable*bp/10000 using integers
        return ['subtotal' => $sub, 'discount' => $disc, 'tax' => $tax, 'total' => $taxable + $tax, 'lines' => $lineTotals, 'valid' => $disc <= $sub];
    }
    public static function roundHalfUp(float $v): int { return (int) floor($v + 0.5); }
    public static function thbToSatang(string|float|int $thb): int
    {
        // string-based to avoid binary float drift (e.g. 19.99)
        $s = is_string($thb) ? trim($thb) : number_format((float) $thb, 2, '.', '');
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $s)) throw new \InvalidArgumentException('amount');
        [$i, $f] = array_pad(explode('.', $s), 2, '0');
        return (int) $i * 100 + (int) str_pad($f, 2, '0');
    }
    public static function format(int $satang, string $locale = 'th'): string
    {
        $neg = $satang < 0 ? '−' : '';
        $n = number_format(abs($satang) / 100, 2, '.', ',');
        return $locale === 'th' ? "{$neg}฿{$n}" : "{$neg}THB {$n}";
    }
}
