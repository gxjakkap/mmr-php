<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Db, DomainError, Policy};
use App\Domain\AssessmentEngine;

final class AssessmentService
{
    public function __construct(private Db $db) {}

    public function run(Actor $a, string $garmentId, array $opts): string
    {
        $g = (new GarmentService($this->db))->getOwned($a, $garmentId);
        $outcome = Validate::enum($opts['desiredOutcome'] ?? '', AssessmentEngine::OUTCOMES, 'desiredOutcome');
        $budget = Validate::optInt($opts['budgetMaxThb'] ?? null, 'budget', 0, 1_000_000);
        $prefs = Validate::optText($opts['preferences'] ?? '', 'preferences', 1000);
        $input = [
            'category' => $g['category'], 'fabric' => $g['fabric_primary'], 'condition' => $g['condition'],
            'defects' => json_list($g['defects']), 'hasMeasurements' => json_list($g['measurements']) !== [],
            'photoCount' => (int) $this->db->value('SELECT COUNT(*) FROM garment_images WHERE garment_id = ?', [$g['id']]),
            'desiredOutcome' => $outcome, 'budgetMaxThb' => $budget,
            'hasExtraMaterial' => !empty($opts['hasExtraMaterial']), 'preferences' => $prefs,
        ];
        $recs = AssessmentEngine::assess($input);
        $id = Db::uuid();
        $this->db->tx(function (Db $db) use ($id, $g, $a, $input, $recs) {
            $db->insert('garment_assessments', ['id' => $id, 'garment_id' => $g['id'], 'owner_id' => $a->id,
                'engine_version' => AssessmentEngine::VERSION, 'inputs' => json_encode($input, JSON_UNESCAPED_UNICODE),
                'summary' => json_encode(['top' => array_column(array_slice($recs, 0, 3), 'pathway')])]);
            foreach ($recs as $r) {
                $db->insert('assessment_recommendations', ['id' => Db::uuid(), 'assessment_id' => $id, 'pathway' => $r['pathway'],
                    'suitability' => $r['suitability'], 'complexity' => $r['complexity'], 'cost_min_thb' => $r['costMinThb'],
                    'cost_max_thb' => $r['costMaxThb'], 'rationale' => json_encode($r['rules']), 'advantages' => json_encode($r['advantages']),
                    'disadvantages' => json_encode($r['disadvantages']), 'materials' => json_encode($r['materials']),
                    'uncertainty' => $r['uncertainty'], 'next_steps' => json_encode($r['nextSteps']), 'rank' => $r['rank']]);
            }
        });
        return $id;
    }

    public function get(Actor $a, string $id): array
    {
        $as = $this->db->one('SELECT a.*, g.name AS garment_name FROM garment_assessments a JOIN garments g ON g.id = a.garment_id WHERE a.id = ?', [Validate::uuid($id)]);
        if (!$as || !Policy::canViewGarment($this->db, $a, $as['garment_id'])) throw DomainError::notFound();
        $as['recommendations'] = $this->db->all('SELECT * FROM assessment_recommendations WHERE assessment_id = ? ORDER BY `rank`', [$as['id']]);
        return $as;
    }
}
