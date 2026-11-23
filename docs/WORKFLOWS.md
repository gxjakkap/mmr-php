# Workflows

## Design request state machine
```mermaid
stateDiagram-v2
  [*] --> DRAFT
  DRAFT --> SUBMITTED: customer
  SUBMITTED --> UNDER_REVIEW: admin
  UNDER_REVIEW --> NEEDS_INFORMATION: admin
  UNDER_REVIEW --> FEASIBILITY_REVIEW: admin
  UNDER_REVIEW --> QUOTE_PREPARING: RequestService::assignDesigner
  FEASIBILITY_REVIEW --> QUOTE_PREPARING: RequestService::assignDesigner
  NEEDS_INFORMATION --> UNDER_REVIEW: customer / admin
  QUOTE_PREPARING --> NEEDS_INFORMATION: designer / admin
  QUOTE_PREPARING --> QUOTE_SENT: RequestService::submitProposal
  QUOTE_SENT --> QUOTE_ACCEPTED: respondQuotation(accept)
  QUOTE_SENT --> QUOTE_REJECTED: respondQuotation(reject)
  QUOTE_REJECTED --> QUOTE_PREPARING: designer / admin (revision)
  QUOTE_ACCEPTED --> [*]
```
Cancellation edges: customer (DRAFT, SUBMITTED, NEEDS_INFORMATION, QUOTE_SENT, QUOTE_REJECTED) and admin (all pre-quote states).

## Order state machine (created on acceptance)
```mermaid
stateDiagram-v2
  [*] --> AWAITING_PAYMENT
  AWAITING_PAYMENT --> PAYMENT_CONFIRMED: OrderService::recordManualPayment (system)
  PAYMENT_CONFIRMED --> AWAITING_GARMENT: admin
  AWAITING_GARMENT --> GARMENT_RECEIVED: admin
  GARMENT_RECEIVED --> PHYSICAL_INSPECTION: admin / tailor
  PHYSICAL_INSPECTION --> DESIGN_FINALIZATION: admin / tailor
  PHYSICAL_INSPECTION --> REFUND_REVIEW: admin (not feasible)
  DESIGN_FINALIZATION --> IN_PRODUCTION: admin
  IN_PRODUCTION --> QUALITY_CONTROL: tailor / admin
  QUALITY_CONTROL --> READY_TO_SHIP: qc
  QUALITY_CONTROL --> REWORK_REQUIRED: qc
  REWORK_REQUIRED --> IN_PRODUCTION: tailor / admin
  READY_TO_SHIP --> SHIPPED: admin
  SHIPPED --> DELIVERED: admin
  DELIVERED --> COMPLETED: customer / admin
  DELIVERED --> REFUND_REVIEW: customer / admin
  REFUND_REVIEW --> CANCELLED: admin
  REFUND_REVIEW --> COMPLETED: admin
```
Plus: AWAITING_PAYMENT → CANCELLED (customer/admin); PAYMENT_CONFIRMED / AWAITING_GARMENT → REFUND_REVIEW (admin).

**Physical inspection rule.** Quotations are `is_provisional`. If inspection shows the design cannot be made as
quoted, the order goes to REFUND_REVIEW, or (planned M2) a *new* quotation revision is issued for explicit
customer approval. Accepted quotations are never edited.

## Quotation rules
- Lines: design_fee, labor, material, shipping, platform_fee, discount, other. Totals computed on the server in `App\Domain\Money::totals` (the form's live estimate is display-only); a DB trigger forbids changing amounts afterwards.
- VAT 7% on (subtotal − discount), rounded half-up to the satang (integer arithmetic).
- One `sent` quotation at a time; a revision supersedes; history is kept (`revision_no`, `supersedes_id`).
- Expired quotations cannot be accepted. *(An expiry sweep that persists status `expired` is planned.)*

## Assessment
Engine `rules-1.0.0` (`app/Domain/AssessmentEngine.php`). Inputs: garment category, fabric, condition, defects,
whether measurements exist, photo count, desired outcome, budget, extra material. Output per pathway:
suitability (recommended / possible / not_advised), complexity, indicative cost band, advantages,
considerations, materials, uncertainty, next steps, and the **rule ids** that fired. No numeric score.

| Rule | Condition → effect |
|---|---|
| R-REUSE-1/2/3 | excellent/good & no repairable defect → recommended; fair → possible; else not advised |
| R-REPAIR-1/2/3 | repairable defect present → recommended; fair+ wear → possible; else not advised |
| R-REPAIR-4 | crotch wear → "may recur" |
| R-REDESIGN-1/2 | fair or worse, or "new look" → recommended; else possible |
| R-REDESIGN-3 | leather/silk → complexity high |
| R-REDESIGN-4 | no extra material → "fabric may be limited" |
| R-UPCYCLE-1/2 | worn/damaged → recommended; else possible |
| R-REWEAR-1/2/3 | excellent/good, no defects → recommended; ≤fair, no repairable → possible (disclose); else repair first |
| R-SWAP-1/2/3 | similar to rewear, more lenient |
| R-RECYCLE-1/2/3 | damaged & ≥3 defects → recommended; worn+ → possible; else not advised |
| R-RECYCLE-4 | blends → harder to recycle |
| R-BUDGET-1 | budget < band minimum → downgrade one level + flag |
| R-UNC-PHOTO | no photos → uncertainty up one level |
| R-UNC-MEAS | redesign without measurements → high uncertainty |
Ordering: suitability, then pathways matching the desired outcome, then canonical order.
Cost bands are ops assumptions (D-007) and must be validated before launch.
