<?php
declare(strict_types=1);
namespace App\Domain;

/**
 * Mirror of request_transitions / order_transitions (database/migrations). The tables are the source of truth
 * used by the services; this mirror renders buttons. tests/WorkflowParityTest checks they match.
 */
final class Workflow
{
    public const REQUEST_STATUSES = ['DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'NEEDS_INFORMATION', 'FEASIBILITY_REVIEW',
        'QUOTE_PREPARING', 'QUOTE_SENT', 'QUOTE_ACCEPTED', 'QUOTE_REJECTED', 'CANCELLED'];
    public const ORDER_STATUSES = ['AWAITING_PAYMENT', 'PAYMENT_CONFIRMED', 'AWAITING_GARMENT', 'GARMENT_RECEIVED',
        'PHYSICAL_INSPECTION', 'DESIGN_FINALIZATION', 'IN_PRODUCTION', 'QUALITY_CONTROL', 'REWORK_REQUIRED',
        'READY_TO_SHIP', 'SHIPPED', 'DELIVERED', 'COMPLETED', 'CANCELLED', 'REFUND_REVIEW'];
    public const ORDER_MILESTONES = ['AWAITING_PAYMENT', 'PAYMENT_CONFIRMED', 'AWAITING_GARMENT', 'GARMENT_RECEIVED',
        'PHYSICAL_INSPECTION', 'DESIGN_FINALIZATION', 'IN_PRODUCTION', 'QUALITY_CONTROL', 'READY_TO_SHIP', 'SHIPPED', 'DELIVERED', 'COMPLETED'];

    public const REQUEST_TRANSITIONS = [
        ['DRAFT', 'SUBMITTED', 'customer'], ['DRAFT', 'CANCELLED', 'customer'], ['SUBMITTED', 'CANCELLED', 'customer'],
        ['SUBMITTED', 'UNDER_REVIEW', 'admin'], ['SUBMITTED', 'CANCELLED', 'admin'],
        ['UNDER_REVIEW', 'NEEDS_INFORMATION', 'admin'], ['UNDER_REVIEW', 'FEASIBILITY_REVIEW', 'admin'],
        ['UNDER_REVIEW', 'QUOTE_PREPARING', 'system'], ['UNDER_REVIEW', 'CANCELLED', 'admin'],
        ['NEEDS_INFORMATION', 'UNDER_REVIEW', 'customer'], ['NEEDS_INFORMATION', 'UNDER_REVIEW', 'admin'],
        ['NEEDS_INFORMATION', 'CANCELLED', 'customer'], ['NEEDS_INFORMATION', 'CANCELLED', 'admin'],
        ['FEASIBILITY_REVIEW', 'QUOTE_PREPARING', 'system'], ['FEASIBILITY_REVIEW', 'NEEDS_INFORMATION', 'admin'],
        ['FEASIBILITY_REVIEW', 'CANCELLED', 'admin'],
        ['QUOTE_PREPARING', 'NEEDS_INFORMATION', 'designer'], ['QUOTE_PREPARING', 'NEEDS_INFORMATION', 'admin'],
        ['QUOTE_PREPARING', 'QUOTE_SENT', 'system'], ['QUOTE_PREPARING', 'CANCELLED', 'admin'],
        ['QUOTE_SENT', 'QUOTE_ACCEPTED', 'system'], ['QUOTE_SENT', 'QUOTE_REJECTED', 'system'], ['QUOTE_SENT', 'CANCELLED', 'customer'],
        ['QUOTE_REJECTED', 'QUOTE_PREPARING', 'designer'], ['QUOTE_REJECTED', 'QUOTE_PREPARING', 'admin'],
        ['QUOTE_REJECTED', 'CANCELLED', 'customer'], ['QUOTE_REJECTED', 'CANCELLED', 'admin'],
    ];
    public const ORDER_TRANSITIONS = [
        ['AWAITING_PAYMENT', 'PAYMENT_CONFIRMED', 'system'], ['AWAITING_PAYMENT', 'CANCELLED', 'customer'], ['AWAITING_PAYMENT', 'CANCELLED', 'admin'],
        ['PAYMENT_CONFIRMED', 'AWAITING_GARMENT', 'admin'], ['AWAITING_GARMENT', 'GARMENT_RECEIVED', 'admin'],
        ['GARMENT_RECEIVED', 'PHYSICAL_INSPECTION', 'admin'], ['GARMENT_RECEIVED', 'PHYSICAL_INSPECTION', 'tailor'],
        ['PHYSICAL_INSPECTION', 'DESIGN_FINALIZATION', 'admin'], ['PHYSICAL_INSPECTION', 'DESIGN_FINALIZATION', 'tailor'],
        ['PHYSICAL_INSPECTION', 'REFUND_REVIEW', 'admin'], ['DESIGN_FINALIZATION', 'IN_PRODUCTION', 'admin'],
        ['IN_PRODUCTION', 'QUALITY_CONTROL', 'tailor'], ['IN_PRODUCTION', 'QUALITY_CONTROL', 'admin'],
        ['QUALITY_CONTROL', 'READY_TO_SHIP', 'qc'], ['QUALITY_CONTROL', 'REWORK_REQUIRED', 'qc'],
        ['REWORK_REQUIRED', 'IN_PRODUCTION', 'tailor'], ['REWORK_REQUIRED', 'IN_PRODUCTION', 'admin'],
        ['READY_TO_SHIP', 'SHIPPED', 'admin'], ['SHIPPED', 'DELIVERED', 'admin'],
        ['DELIVERED', 'COMPLETED', 'customer'], ['DELIVERED', 'COMPLETED', 'admin'],
        ['DELIVERED', 'REFUND_REVIEW', 'customer'], ['DELIVERED', 'REFUND_REVIEW', 'admin'],
        ['PAYMENT_CONFIRMED', 'REFUND_REVIEW', 'admin'], ['AWAITING_GARMENT', 'REFUND_REVIEW', 'admin'],
        ['REFUND_REVIEW', 'CANCELLED', 'admin'], ['REFUND_REVIEW', 'COMPLETED', 'admin'],
    ];

    /** Manual (non-system) moves for display. */
    public static function nextRequest(string $from, array $actors): array
    {
        return self::next(self::REQUEST_TRANSITIONS, $from, $actors);
    }
    public static function nextOrder(string $from, array $actors): array
    {
        return self::next(self::ORDER_TRANSITIONS, $from, $actors);
    }
    private static function next(array $table, string $from, array $actors): array
    {
        $out = [];
        foreach ($table as [$f, $t, $a]) if ($f === $from && $a !== 'system' && in_array($a, $actors, true)) $out[$t] = true;
        return array_keys($out);
    }
}
