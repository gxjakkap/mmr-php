# Roadmap & backlog

## M1 — Vertical slice ✅ (this delivery)
Registration → login → upload garment → wardrobe → assessment → template → design request → admin review →
assign designer → designer proposal + quotation → customer accepts → order created → payment recorded → order moves.

## M2 — Production & QC (next)
- Tailor portal: assigned jobs, feasibility confirm, missing materials, progress photos, issues, submit to QC
- Admin: partner onboarding, skill verification, capacity calendar, `assign_partner` UI
- QC checklist UI (8 checks, photo evidence, pass/fail/rework, reinspection attempts)
- Post-inspection quotation revision flow with customer re-approval
- Designer sketch uploads (`design-assets`), customer reference images
- Quotation expiry sweep (scheduled function) · in-app notifications
- Optional move to a framework (Laravel) if the team grows — services/Policy port directly

## M3 — Circular marketplace
- Rewear listings with moderation, purchase requests, separate marketplace order lifecycle
- Swap listings, mutual acceptance, fees, two-leg shipping, disputes
- Carbon activity capture + calculation records + CSV export (no verified claims)
- Support tickets UI

## M4 — MEMORii Style
Outfit inspiration, rule-based and AI-assisted styling, outfit planner, virtual try-on adapter.

## Backlog prioritisation (MoSCoW for launch)
| Must | Should | Could | Won't (yet) |
|---|---|---|---|
| M1 + M2, payment gateway adapter, SMTP mail adapter, privacy policy/PDPA consent, backups | marketplace, support tickets, shipping tracking | swap, carbon export, AI concepts | try-on, native apps |

## User stories (M1) with acceptance criteria
1. *As a customer I can add a garment with photos* — saved row owned by me; ≤8 images ≤8 MB JPEG/PNG/WebP; others cannot see it. ✅ tested
2. *As a customer I can get a second-life assessment* — 7 pathways with reasons and rule ids; labelled preliminary; stored with inputs + engine version. ✅ tested
3. *As a customer I can request a template redesign* — must acknowledge feasibility; draft or submit. ✅
4. *As an admin I can review and assign a designer* — only admins; only active designers; moves to QUOTE_PREPARING. ✅ tested
5. *As a designer I can send a proposal and quotation* — only when assigned and QUOTE_PREPARING; totals computed server-side. ✅ tested
6. *As a customer I can accept or decline* — only my quotation, only while sent & unexpired; acceptance creates the order once. ✅ tested
7. *As an admin I can record a bank transfer* — immutable payment; order confirms only when fully paid. ✅ tested

## Risk register
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Garment cannot be made as designed after inspection | High | Med | provisional quotes, inspection state, refund review, explicit re-approval |
| Tailor lacks the skill for a job | Med | High | verified skill gate + capacity in `assign_partner` |
| Data leak across customers (IDOR) | Low | High | Policy checks + owner-scoped queries, 404 on foreign ids, IDOR tests |
| Overstated sustainability claims | Med | High | carbon results cannot be marked verified; separate observed vs estimated |
| Payment disputes / reconciliation errors | Med | Med | immutable ledger, references required, audit log |
| PDPA (Thai data protection) non-compliance | Med | High | minimised profile exposure; consent + privacy policy required before launch |
| Indicative prices mislead customers | Med | Med | "indicative" labels; D-007 validation task |
| Shared-host misconfiguration (docroot not /public) | Med | High | root .htaccess blocks app/storage/.env; SETUP checklist |
| Authorization bug (no DB-level RLS in MySQL) | Med | High | single Policy class, services-only data access, 44 integration + 20 HTTP tests |
