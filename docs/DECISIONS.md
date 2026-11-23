# Decision log

| ID | Decision | Rationale |
|---|---|---|
| D-101 | Rewrite from Next.js/Supabase to **PHP 8.1+ with MySQLi**, no framework | requested by the product owner; runs on standard Thai shared hosting |
| D-102 | No runtime Composer dependencies | deployable by FTP/cPanel; fewer supply-chain risks. Tests use a tiny built-in harness |
| D-103 | Authorization in a single `Policy` class + services; controllers never query | MySQL lacks RLS; one place to audit |
| D-104 | Foreign record ids return 404, not 403 | do not reveal that another user's record exists |
| D-105 | Keep DB-level integrity (FKs, CHECKs, append-only triggers, immutable quotation amounts) | defence in depth even if application code has a bug |
| D-106 | Transition tables in SQL are the source of truth; PHP mirror parity-tested | same as the Supabase version (D-003) |
| D-107 | Email verification required before login; tokens stored hashed, single-use | brief §4.1; token leakage from the DB is not enough to use them |
| D-108 | `MAIL_DRIVER=log` default | no SMTP credentials yet; messages go to `storage/logs/mail.log`, clearly labelled in the UI |
| D-109 | Uploads outside web root, served via access-checked controller | prevents direct access and script upload execution |
| D-110 | Strict CSP (no inline scripts/styles) | XSS hardening; the CSS uses utility classes instead of inline styles |
| D-111 | Hand-written CSS design system instead of Tailwind | no Node build step on the server; tokens at the top of `public/assets/app.css` |
| D-112 | Progressive enhancement: every flow works without JavaScript | robustness on low-end phones |
| D-006 | Payments: manual bank-transfer recording only | no gateway credentials; no fake pay button |
| D-007 | Assessment cost bands are placeholder ops assumptions | shown as "indicative" until validated |
| D-008 | Ordinal suitability, no numeric score | brief §4.4 |
| D-013 | No stock photography bundled | the six reference images were not provided |
