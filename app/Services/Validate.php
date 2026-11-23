<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\DomainError;
use App\Domain\AssessmentEngine;

/** Input validation shared by controllers and services (services re-validate everything). */
final class Validate
{
    public const CATEGORIES = ['jeans', 'denim_jacket', 'denim_skirt', 'denim_shirt', 'dress', 'shirt', 'tshirt', 'jacket', 'skirt', 'trousers', 'bag', 'other'];
    public const FABRICS = ['denim', 'cotton', 'linen', 'wool', 'silk', 'polyester', 'blend', 'leather', 'other'];
    public const DEFECTS = ['hole', 'tear', 'stain', 'fading', 'broken_zip', 'missing_button', 'worn_hem', 'crotch_wear', 'stretched', 'pilling', 'seam_split', 'other'];
    public const MEASUREMENTS = ['waist_cm', 'hip_cm', 'length_cm', 'inseam_cm', 'chest_cm', 'shoulder_cm', 'sleeve_cm'];
    public const ROLES = ['customer', 'admin', 'designer', 'tailor', 'qc', 'support'];
    public const SKILLS = ['basic_alteration', 'denim_repair', 'pattern_making', 'garment_reconstruction', 'advanced_upcycling', 'complex_dressmaking'];

    public static function uuid(mixed $v, string $field = 'id'): string
    {
        if (!is_string($v) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $v)) throw DomainError::invalid("invalid {$field}");
        return strtolower($v);
    }
    public static function text(mixed $v, string $field, int $max, int $min = 0): string
    {
        $s = is_string($v) ? trim($v) : '';
        if (!mb_check_encoding($s, 'UTF-8')) throw DomainError::invalid("invalid {$field}");
        $len = mb_strlen($s);
        if ($len < $min || $len > $max) throw DomainError::invalid("{$field} length must be {$min}-{$max}");
        return $s;
    }
    public static function optText(mixed $v, string $field, int $max): ?string
    {
        $s = self::text($v, $field, $max);
        return $s === '' ? null : $s;
    }
    public static function enum(mixed $v, array $allowed, string $field): string
    {
        if (!is_string($v) || !in_array($v, $allowed, true)) throw DomainError::invalid("invalid {$field}");
        return $v;
    }
    public static function optInt(mixed $v, string $field, int $min, int $max): ?int
    {
        if ($v === null || $v === '') return null;
        if (!is_numeric($v) || (int) $v != $v) throw DomainError::invalid("invalid {$field}");
        $i = (int) $v;
        if ($i < $min || $i > $max) throw DomainError::invalid("{$field} out of range");
        return $i;
    }
    public static function email(mixed $v): string
    {
        $s = strtolower(trim((string) $v));
        if (strlen($s) > 254 || !filter_var($s, FILTER_VALIDATE_EMAIL)) throw DomainError::invalid('invalid email');
        return $s;
    }
    public static function password(mixed $v): string
    {
        if (!is_string($v) || strlen($v) < 8 || strlen($v) > 72) throw DomainError::invalid('password_length');
        return $v;
    }

    /** "98% cotton, 2% elastane" -> [{fiber, pct}] */
    public static function composition(?string $text): array
    {
        if (!$text) return [];
        $out = [];
        foreach (preg_split('/[,;\n]/u', $text) as $part) {
            if (preg_match('/^\s*(\d{1,3}(?:\.\d+)?)\s*%\s*(.+?)\s*$/u', $part, $m)) {
                $pct = (float) $m[1];
                $fiber = mb_substr(mb_strtolower($m[2]), 0, 40);
                if ($pct > 0 && $pct <= 100 && $fiber !== '') $out[] = ['fiber' => $fiber, 'pct' => $pct + 0];
            }
        }
        return $out;
    }

    public static function garment(array $in): array
    {
        $defects = array_values(array_unique(array_filter((array) ($in['defects'] ?? []), 'is_string')));
        foreach ($defects as $d) self::enum($d, self::DEFECTS, 'defect');
        $m = [];
        foreach (self::MEASUREMENTS as $k) {
            $v = $in['measurements'][$k] ?? '';
            if ($v === '' || $v === null) continue;
            if (!is_numeric($v) || (float) $v < 1 || (float) $v > 400) throw DomainError::invalid("invalid {$k}");
            $m[$k] = round((float) $v, 1);
        }
        $compText = self::optText($in['composition_text'] ?? '', 'composition', 200);
        return [
            'name' => self::text($in['name'] ?? '', 'name', 120, 1),
            'category' => self::enum($in['category'] ?? '', self::CATEGORIES, 'category'),
            'fabric_primary' => self::enum($in['fabric_primary'] ?? '', self::FABRICS, 'fabric'),
            'fabric_composition' => json_encode(self::composition($compText), JSON_UNESCAPED_UNICODE),
            'color' => self::optText($in['color'] ?? '', 'color', 40),
            'condition' => self::enum($in['condition'] ?? '', AssessmentEngine::CONDITIONS, 'condition'),
            'defects' => json_encode($defects),
            'condition_notes' => self::optText($in['condition_notes'] ?? '', 'condition_notes', 2000),
            'sentimental_notes' => self::optText($in['sentimental_notes'] ?? '', 'sentimental_notes', 2000),
            'measurements' => json_encode((object) $m),
        ];
    }
}
