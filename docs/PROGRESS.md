# Progress — 2026-11-20 (PHP + MySQLi version)

## Completed & tested
- 6 migrations, 45 tables; append-only and immutability triggers; transition tables
- Auth: register, email verification (required), login with role redirect, logout, password reset, profile, rate limiting
- Wardrobe CRUD, multi-photo upload (content-validated, outside web root), filters, history, delete safeguard
- Second-Life Assessment engine (port of rules-1.0.0) + stored results
- Creative Studio (template + custom), design requests, messages (with staff-only internal notes), timeline
- Designer portal: proposal + itemised quotation, live estimate, server totals
- Customer accept/decline → order; order progress page
- Admin: live metrics, requests, designer assignment, orders, manual payment, transitions, users/roles, audit log
- Thai default / English, responsive mobile-first CSS design system
- 25 unit + 44 integration + 20 HTTP end-to-end tests passing on MariaDB 10.11 / PHP 8.3

## Demo / placeholder (labelled in the UI)
AI-assisted design (not enabled), payments (manual transfer only), marketplace/swap/recycle next steps ("in development"),
email in development is written to a log file.

## Planned
M2: tailor portal, QC checklist UI, partner onboarding UI, post-inspection quotation revision, sketch uploads, notifications.

## Needs external credentials / services
SMTP or mail relay · payment gateway · carrier API · AI image provider.

## Known limitations
- Not reviewed visually in a real browser; no screenshots taken (no browser in the build environment).
- Tested on MariaDB only; MySQL 8 should work (standard syntax) but has not been run.
- The Next.js/Supabase version delivered earlier is superseded by this one.
