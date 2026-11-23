# Testing

Zero-dependency PHP test harness (`tests/lib.php`) so tests run on hosts without Composer.

| Suite | Command | What it proves | Status |
|---|---|---|---|
| Unit | `php tests/run.php unit` | assessment rules, money/VAT, THB parsing, workflow parity with SQL, TH/EN key parity, validation | 25 passing |
| Integration (real MySQL/MariaDB) | `php tests/run.php` | full slice through services + authorization (IDOR, roles, transitions, immutability, skill gate, rejection/revision/expiry, messages) | 57 passing (incl. Rewear, Swap, favourites, outfits, slips, notifications) |
| HTTP end-to-end | `php -S 127.0.0.1:8000 -t public public/router.php` then `php tests/http_e2e.php http://127.0.0.1:8000` | real web stack: sessions, CSRF, redirects, uploads, media access, role gates, rate limiting, every page renders | 22 passing, 0 PHP warnings (incl. guest browsing, v3 pages, Rewear over HTTP) |

Integration tests create and drop a throwaway database (name must end in `_test`). Configure with
`DB_TEST_HOST`, `DB_TEST_USER`, `DB_TEST_PASS`, `DB_TEST_NAME` (defaults: 127.0.0.1 / memorii / memorii / memorii_test).
The HTTP test uses the database in `.env` and creates uniquely named users each run; run it against a development database only.

All results above are from MariaDB 10.11 + PHP 8.3 on 2026-11-23. Visual check: headless Chrome screenshots at 1360px and 390px.

## Not covered yet
Visual review in real browsers/devices, accessibility audit, MySQL 8 (only MariaDB was run here), load testing, real email delivery.
