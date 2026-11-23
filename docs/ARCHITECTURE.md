# Architecture (PHP 8 + MySQLi)

```
Browser ── HTML forms (+ small progressive JS) ── HTTPS
   ▼
public/index.php  (front controller; document root = /public)
   ├─ Http::securityHeaders()      CSP, X-Frame-Options, nosniff, HSTS on HTTPS
   ├─ Session::start()             httponly + SameSite=Lax cookie, strict mode, idle timeout
   ├─ CSRF check on every POST     per-session token, hash_equals
   ├─ Router (app/routes.php)      route-level gate: guest | auth | <role>
   ▼
app/Controllers/*   thin: read input → call a service → render a view / redirect (PRG)
app/Services/*      ALL business rules + authorization + validation + transactions
app/Core/Policy.php record-level access rules (the PHP equivalent of row-level security)
app/Domain/*        pure logic: AssessmentEngine, Money, Workflow (no I/O)
app/Core/Db.php     MySQLi wrapper — prepared statements only, transactions, UUIDs
app/Views/*         plain PHP templates; every value escaped with e()
   ▼
MySQL 8 / MariaDB 10.6+ (InnoDB, utf8mb4)   storage/uploads (outside web root)
```

## Why it is structured this way
- **No framework, no runtime Composer packages** → runs on ordinary Thai shared hosting (cPanel/DirectAdmin) as well as a VPS.
- **MySQL has no row-level security**, so authorization is centralised: route gate → `Policy` → owner-scoped SQL in services.
  Controllers never touch the database. Records the caller may not access return 404.
- **The database still guards integrity**: FKs, CHECK constraints, unique keys, and triggers that make payments, payment events,
  status histories and audit logs append-only, and quotation amounts immutable.
- **State machines live in tables** (`request_transitions`, `order_transitions`). Services check every move against them inside a
  transaction with `SELECT … FOR UPDATE`; `app/Domain/Workflow.php` mirrors them for rendering buttons and is parity-tested.
- **Money is integer satang**, computed only on the server.
- **Uploads**: validated by content (`finfo` + `getimagesize`), ≤ 8 MB, JPEG/PNG/WebP, random file names, stored outside the web
  root, streamed by `MediaController` after `Policy::canViewGarment`.
- **Adapters** for external services: `Mailer` (log | mail), payment = manual bank transfer, AI/shipping = not connected.

## Route map
| Route | Access | Purpose |
|---|---|---|
| `GET /` · `POST /locale` | all | inspiration · language switch |
| `/login` `/register` `/verify-email` `/forgot-password` `/reset-password` · `POST /logout` | guest / auth | authentication |
| `/account` | auth | profile, language, roles |
| `/wardrobe` · `/wardrobe/new` · `/wardrobe/{id}` · `/wardrobe/{id}/edit` · `POST /wardrobe/{id}/delete` · `POST /wardrobe/{id}/images` · `POST /images/{id}/delete` | auth (owner) | digital wardrobe |
| `/wardrobe/{id}/assess` · `/assessments/{id}` | auth (owner) | second-life assessment |
| `GET /media/garment/{id}` | auth (Policy) | private image streaming |
| `/studio` · `POST /studio` | auth | creative studio / design requests |
| `/requests` · `/requests/{id}` · `POST /requests/{id}/transition` · `POST /requests/{id}/message` · `POST /quotations/{id}/respond` | auth (Policy) | consultation & quotations |
| `/orders/{id}` · `POST /orders/{id}/transition` | auth (Policy) | order progress |
| `/admin…` (overview, requests, assign, orders, payment, users, grant, audit) | admin | operations |
| `/designer` · `/designer/requests/{id}` · `POST …/proposal` | designer | proposals & quotations |

## Service contract (what each call enforces)
| Method | Caller | Effect |
|---|---|---|
| `RequestService::transition` | per `request_transitions` actor | non-system moves; opens conversation on submit |
| `RequestService::assignDesigner` | admin | assigns active designer → QUOTE_PREPARING |
| `RequestService::submitProposal` | assigned designer, status QUOTE_PREPARING | new version + quotation revision, server totals → QUOTE_SENT |
| `RequestService::respondQuotation` | request owner, quote `sent` & unexpired | accept → order AWAITING_PAYMENT (atomic); reject → QUOTE_REJECTED |
| `OrderService::transition` | per `order_transitions` actor | order moves + history + audit |
| `OrderService::recordManualPayment` | admin | append-only payment; confirms only when fully paid; unique reference |
| `OrderService::assignPartner` | admin | refuses unverified partner, unverified skill, or no capacity |
| `UserService::grantRole` | admin | grants role (+ designer profile) |
Errors are `DomainError` (forbidden → 403, not_found → 404, invalid → 422 / flash message on POST).
