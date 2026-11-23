# Roles & permission matrix

Roles are rows in `user_roles` (a user may hold several). New sign-ups get `customer` via trigger.
Only admins grant roles (`UserService::grantRole`); the first admin is bootstrapped on the server with `php bin/create-admin.php <email>`.

Legend: **R** read · **W** write · **own** only own records · **asg** only assigned records · — none

| Resource | Guest | Customer | Designer | Tailor | QC | Support | Admin |
|---|---|---|---|---|---|---|---|
| design_templates | R | R | R | R | R | R | R W |
| profiles | — | R W own | own | own | own | R | R |
| user_roles | — | R own | R own | R own | R own | R own | R W |
| garments / images / assessments | — | R W own | R asg | R asg | — | R | R |
| design_requests | — | R own, W own while DRAFT | R asg | — | — | R | R |
| request status moves | — | per table (submit, cancel, reply to info) | per table (ask info, reopen) | — | — | — | per table |
| design_versions / quotations | — | R own | R asg, create via service | — | — | R | R |
| accept / reject quotation | — | own (service) | — | — | — | — | — |
| messages | — | R W own request (no internal) | R W asg (+internal) | — | — | R W | R W |
| orders / items / history | — | R own | — | R asg | R | R | R |
| order status moves | — | cancel unpaid, complete / dispute delivered | — | inspection, submit to QC, rework | pass / rework | — | per table |
| payments | — | R own | — | — | — | — | R, record via service |
| production_partners (contact info) | — | **—** | — | R own | — | — | R W |
| partner_skills / capacity | — | — | — | R own | — | — | R W |
| production_jobs / updates | — | — | — | R W asg | R | — | R W |
| qc_inspections / items | — | — | — | — | R W own | — | R |
| marketplace / swap listings *(M3, schema only)* | R active | R active, W own (pending_review) | same | same | same | same | moderate |
| support_tickets | — | R W own | — | — | — | R W | R W |
| emission_factors | — | R | R | R | R | R | R W |
| activity_records | — | — | — | W asg orders | — | — | R W |
| audit_logs | — | — | — | — | — | — | R |

MySQL has no row-level security, so every cell is enforced in PHP: route-level role gates (`app/routes.php`), record-level checks in `app/Core/Policy.php`, and ownership-scoped queries in `app/Services/*`. Controllers never query the database directly. Records the caller may not see return **404** (not 403) so their existence is not revealed. Verified by `tests/integration/WorkflowAuthorizationTest.php` and `tests/http_e2e.php`.
