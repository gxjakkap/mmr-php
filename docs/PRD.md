# MEMORii — Product Requirements Document

**Tagline:** Your Wardrobe. Your Style. Your Second Life.
**Status:** Milestone 1 (vertical slice) implemented in PHP 8 + MySQLi · last updated 2026-11-20

## 1. Problem
Customers own garments — denim first — that they no longer wear but still value. Today the options are scattered
(local tailors with unknown skills, resale apps, donation) and none of them help the customer decide *what is
realistic* for a specific garment, or carry a redesign safely from idea to finished piece.

## 2. Goals
1. Let customers record garments they already own (not only new purchases).
2. Give a transparent, explainable, *preliminary* second-life assessment — never an automatic decision.
3. Run a redesign service end to end: request → designer proposal → quotation → approval → order → production → QC → delivery.
4. Route production only to partners with verified skills and capacity.
5. Collect activity data for future carbon accounting without making unverified claims.

## 3. Non-goals (for now)
- Live payment gateway, carrier APIs, AI image generation (adapters exist; providers not connected).
- Native mobile apps (responsive web, mobile first).
- Verified carbon savings claims.

## 4. Users & roles
Guest, Customer, Admin, Designer, Tailor/Production partner, QC staff, Support staff — see `ROLES.md`.

## 5. Product areas
| Area | Scope | Milestone |
|---|---|---|
| Auth & account | register, login, logout, email verification, password reset, profile, role redirect | **M1 ✅** |
| Digital wardrobe | CRUD, multi-photo upload, composition, measurements, defects, sentimental notes, filters, history | **M1 ✅** |
| Second-Life Assessment | rule engine for 7 pathways with advantages/disadvantages/complexity/cost band/uncertainty/next steps | **M1 ✅** |
| Creative Studio | template mode, custom request mode; AI mode = labelled placeholder | **M1 ✅** (AI: planned) |
| Design consultation | request thread, designer questions (NEEDS_INFORMATION), proposals, revisions, approve/reject | **M1 ✅** |
| Quotation & orders | itemised quotes, VAT, discounts, expiry, revisions, immutable payments, full order state machine | **M1 ✅** (manual transfer only) |
| Admin dashboard | live metrics, requests, orders, payment recording, designer assignment, users/roles, audit log | **M1 ✅** (partial) |
| Designer portal | assigned requests, garment data, proposal + quotation | **M1 ✅** (sketch upload: M2) |
| Tailor portal, QC | schema + skill-gated `OrderService::assignPartner` | **M2** (UI) |
| Marketplace, Swap | schema | **M3** |
| Carbon framework | schema (factors, activity, calculations) | **M3** |
| MEMORii Style (outfits, planner, try-on) | — | **M4+** |

## 6. Principles (from the brief, enforced in code)
- The system never makes irreversible decisions for the customer.
- Photo-based assessment is labelled preliminary; physical inspection precedes the final commitment.
- No numeric feasibility score without a documented basis — the engine uses ordinal levels only.
- AI output is labelled as concept (`design_versions.is_ai_concept`).
- Accepted quotations are never changed; changes create a new revision requiring new approval.
- Every metric is computed from live data or explicitly labelled demo.
- Customers never see tailor contact data.
