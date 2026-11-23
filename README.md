# MEMORii — PHP 8 + MySQLi

**Your Wardrobe. Your Style. Your Second Life.** — circular-fashion platform: redesign old clothes into clothing, bags, accessories and home goods; Rewear marketplace; Swap; outfit planner. Go-live steps: [docs/SETUP.md](docs/SETUP.md).
Framework-free PHP 8.1+, MySQLi prepared statements, MySQL 8 / MariaDB 10.6+, no runtime dependencies.

```bash
cp .env.example .env && php bin/migrate.php
php -S localhost:8000 -t public public/router.php
php tests/run.php            # unit + integration (needs a MySQL user that can create memorii_test)
```
Docs: [PRD](docs/PRD.md) · [Architecture & routes](docs/ARCHITECTURE.md) · [Database](docs/DATABASE.md) · [Roles](docs/ROLES.md) ·
[Workflows](docs/WORKFLOWS.md) · [Roadmap & risks](docs/ROADMAP.md) · [Testing](docs/TESTING.md) · [Decisions](docs/DECISIONS.md) ·
[Setup & deploy](docs/SETUP.md) · [Progress](docs/PROGRESS.md)

```
public/        web root (index.php, assets)      app/Core      router, db, session, csrf, policy, upload, i18n
app/Controllers thin HTTP layer                  app/Services  business rules + authorization
app/Domain     assessment engine, money, workflow app/Views     templates      app/Lang  th / en
database/migrations   bin/ (migrate, create-admin)   storage/ (uploads, logs — not web-accessible)   tests/
```
