<?php
declare(strict_types=1);
namespace App\Domain;

/**
 * MEMORii Second-Life Assessment — transparent rule engine (rules-1.0.0).
 * Deterministic; each recommendation lists the rule ids that produced it; text is returned as i18n keys;
 * no numeric score; results are PRELIMINARY. Rules documented in docs/WORKFLOWS.md.
 */
final class AssessmentEngine
{
    public const VERSION = 'rules-1.0.0';
    public const PATHWAYS = ['reuse', 'repair', 'redesign', 'upcycle', 'rewear', 'swap', 'recycle'];
    public const CONDITIONS = ['excellent', 'good', 'fair', 'worn', 'damaged'];
    public const OUTCOMES = ['keep_wearing', 'new_look', 'sell', 'swap', 'dispose_responsibly'];

    private const REPAIRABLE = ['hole', 'tear', 'broken_zip', 'missing_button', 'worn_hem', 'seam_split', 'crotch_wear'];
    private const COSMETIC = ['fading', 'pilling', 'stain'];
    private const HARD_FABRICS = ['leather', 'silk'];
    private const BLENDS = ['blend', 'polyester'];
    // Indicative bands (THB) — ops assumptions v1, to be validated (D-007).
    private const COST = ['repair' => [150, 800], 'redesign' => [900, 5500], 'upcycle' => [500, 3800]];
    private const ORDER = ['recommended' => 0, 'possible' => 1, 'not_advised' => 2];

    /**
     * @param array{category:string,fabric:string,condition:string,defects:string[],hasMeasurements:bool,photoCount:int,
     *              desiredOutcome:string,budgetMaxThb:?int,hasExtraMaterial:bool} $in
     * @return array<int, array<string, mixed>>
     */
    public static function assess(array $in): array
    {
        $ci = array_search($in['condition'], self::CONDITIONS, true);
        if ($ci === false) throw new \InvalidArgumentException('condition');
        $defects = array_values($in['defects']);
        $repairable = array_values(array_intersect($defects, self::REPAIRABLE));
        $cosmetic = array_values(array_intersect($defects, self::COSMETIC));
        $hard = in_array($in['fabric'], self::HARD_FABRICS, true);
        $photos = (int) $in['photoCount'];
        $out = [];
        $base = static fn(string $p) => ['pathway' => $p, 'suitability' => 'possible', 'complexity' => null, 'costMinThb' => null,
            'costMaxThb' => null, 'advantages' => [], 'disadvantages' => [], 'materials' => [], 'uncertainty' => 'medium',
            'nextSteps' => [], 'rules' => [], 'rank' => 0];

        // REUSE
        $r = $base('reuse');
        if ($ci <= 1 && !$repairable) { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-REUSE-1'; }
        elseif ($ci === 2) { $r['suitability'] = 'possible'; $r['rules'][] = 'R-REUSE-2'; }
        else { $r['suitability'] = 'not_advised'; $r['rules'][] = 'R-REUSE-3'; }
        $r['complexity'] = 'low';
        array_push($r['advantages'], 'adv.reuse.zero_cost', 'adv.reuse.lowest_impact');
        if ($repairable) $r['disadvantages'][] = 'dis.reuse.defects_remain';
        $r['nextSteps'][] = 'next.style_in_wardrobe';
        $r['uncertainty'] = 'low';
        $out[] = $r;

        // REPAIR
        $r = $base('repair');
        if ($repairable) { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-REPAIR-1'; }
        elseif ($ci >= 2) { $r['suitability'] = 'possible'; $r['rules'][] = 'R-REPAIR-2'; }
        else { $r['suitability'] = 'not_advised'; $r['rules'][] = 'R-REPAIR-3'; }
        $r['complexity'] = (count($repairable) >= 3 || $hard) ? 'medium' : 'low';
        [$r['costMinThb'], $r['costMaxThb']] = self::COST['repair'];
        array_push($r['advantages'], 'adv.repair.keeps_original', 'adv.repair.affordable');
        if (in_array('crotch_wear', $defects, true)) { $r['disadvantages'][] = 'dis.repair.may_recur'; $r['rules'][] = 'R-REPAIR-4'; }
        $r['materials'][] = 'mat.thread';
        if (in_array('broken_zip', $defects, true)) $r['materials'][] = 'mat.zip';
        if (array_intersect(['hole', 'tear'], $defects)) $r['materials'][] = 'mat.patch';
        $r['uncertainty'] = ($photos >= 2 && $repairable) ? 'low' : 'medium';
        array_push($r['nextSteps'], 'next.choose_repair_template', 'next.confirm_physical');
        $out[] = $r;

        // REDESIGN
        $r = $base('redesign');
        if ($ci >= 2 || $in['desiredOutcome'] === 'new_look') { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-REDESIGN-1'; }
        else { $r['suitability'] = 'possible'; $r['rules'][] = 'R-REDESIGN-2'; }
        $r['complexity'] = $hard ? 'high' : 'medium';
        if ($hard) $r['rules'][] = 'R-REDESIGN-3';
        [$r['costMinThb'], $r['costMaxThb']] = self::COST['redesign'];
        array_push($r['advantages'], 'adv.redesign.new_garment', 'adv.redesign.keeps_memory');
        array_push($r['disadvantages'], 'dis.redesign.irreversible', 'dis.redesign.higher_cost');
        if (!$in['hasExtraMaterial']) { $r['disadvantages'][] = 'dis.redesign.fabric_limited'; $r['rules'][] = 'R-REDESIGN-4'; }
        array_push($r['materials'], 'mat.extra_fabric_maybe', 'mat.thread', 'mat.closures');
        $r['uncertainty'] = (!$in['hasMeasurements'] || $photos === 0) ? 'high' : 'medium';
        if (!$in['hasMeasurements']) $r['rules'][] = 'R-UNC-MEAS';
        array_push($r['nextSteps'], 'next.open_studio', 'next.add_measurements', 'next.confirm_physical');
        $out[] = $r;

        // UPCYCLE
        $r = $base('upcycle');
        if ($ci >= 3) { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-UPCYCLE-1'; }
        else { $r['suitability'] = 'possible'; $r['rules'][] = 'R-UPCYCLE-2'; }
        $r['complexity'] = 'medium';
        [$r['costMinThb'], $r['costMaxThb']] = self::COST['upcycle'];
        array_push($r['advantages'], 'adv.upcycle.uses_damaged', 'adv.upcycle.accessory');
        $r['disadvantages'][] = 'dis.upcycle.not_wearable_same_way';
        array_push($r['materials'], 'mat.lining', 'mat.hardware');
        $r['uncertainty'] = $photos === 0 ? 'high' : 'medium';
        array_push($r['nextSteps'], 'next.open_studio', 'next.confirm_physical');
        $out[] = $r;

        // REWEAR
        $r = $base('rewear');
        if ($ci <= 1 && !$repairable && !$cosmetic) { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-REWEAR-1'; }
        elseif ($ci <= 2 && !$repairable) { $r['suitability'] = 'possible'; $r['rules'][] = 'R-REWEAR-2'; $r['disadvantages'][] = 'dis.rewear.disclose_wear'; }
        else { $r['suitability'] = 'not_advised'; $r['rules'][] = 'R-REWEAR-3'; $r['disadvantages'][] = 'dis.rewear.repair_first'; }
        $r['complexity'] = 'low';
        array_push($r['advantages'], 'adv.rewear.earn', 'adv.rewear.extends_life');
        $r['disadvantages'][] = 'dis.rewear.platform_fees';
        $r['uncertainty'] = $photos >= 3 ? 'low' : 'medium';
        $r['nextSteps'][] = 'next.marketplace_soon';
        $out[] = $r;

        // SWAP
        $r = $base('swap');
        if ($ci <= 1 && !$repairable) { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-SWAP-1'; }
        elseif ($ci <= 2) { $r['suitability'] = 'possible'; $r['rules'][] = 'R-SWAP-2'; $r['disadvantages'][] = 'dis.swap.disclose'; }
        else { $r['suitability'] = 'not_advised'; $r['rules'][] = 'R-SWAP-3'; }
        $r['complexity'] = 'low';
        $r['advantages'][] = 'adv.swap.refresh_free_of_new';
        $r['disadvantages'][] = 'dis.swap.shipping_costs';
        $r['nextSteps'][] = 'next.swap_soon';
        $out[] = $r;

        // RECYCLE
        $r = $base('recycle');
        if ($ci === 4 && count($defects) >= 3) { $r['suitability'] = 'recommended'; $r['rules'][] = 'R-RECYCLE-1'; }
        elseif ($ci >= 3) { $r['suitability'] = 'possible'; $r['rules'][] = 'R-RECYCLE-2'; }
        else { $r['suitability'] = 'not_advised'; $r['rules'][] = 'R-RECYCLE-3'; $r['disadvantages'][] = 'dis.recycle.still_usable'; }
        $r['complexity'] = 'low';
        $r['advantages'][] = 'adv.recycle.keeps_from_landfill';
        $r['disadvantages'][] = 'dis.recycle.value_lost';
        if (in_array($in['fabric'], self::BLENDS, true)) { $r['disadvantages'][] = 'dis.recycle.blend_hard'; $r['rules'][] = 'R-RECYCLE-4'; }
        $r['nextSteps'][] = 'next.recycle_referral';
        $out[] = $r;

        // Cross-cutting
        foreach ($out as &$x) {
            if ($in['budgetMaxThb'] !== null && $x['costMinThb'] !== null && $in['budgetMaxThb'] < $x['costMinThb']) {
                $x['suitability'] = match ($x['suitability']) { 'recommended' => 'possible', default => 'not_advised' };
                $x['disadvantages'][] = 'dis.over_budget';
                $x['rules'][] = 'R-BUDGET-1';
            }
            if ($photos === 0 && $x['uncertainty'] !== 'high') {
                $x['uncertainty'] = $x['uncertainty'] === 'low' ? 'medium' : 'high';
                $x['rules'][] = 'R-UNC-PHOTO';
            }
        }
        unset($x);

        $preferred = [
            'keep_wearing' => ['repair', 'reuse'], 'new_look' => ['redesign', 'upcycle'], 'sell' => ['rewear'],
            'swap' => ['swap'], 'dispose_responsibly' => ['recycle', 'swap', 'rewear'],
        ][$in['desiredOutcome']] ?? [];
        usort($out, static function ($a, $b) use ($preferred) {
            $s = self::ORDER[$a['suitability']] <=> self::ORDER[$b['suitability']];
            if ($s) return $s;
            $pa = in_array($a['pathway'], $preferred, true) ? 0 : 1;
            $pb = in_array($b['pathway'], $preferred, true) ? 0 : 1;
            if ($pa !== $pb) return $pa <=> $pb;
            return array_search($a['pathway'], self::PATHWAYS, true) <=> array_search($b['pathway'], self::PATHWAYS, true);
        });
        foreach ($out as $i => &$x) $x['rank'] = $i + 1;
        unset($x);
        return $out;
    }
}
