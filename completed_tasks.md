# Project Status

**Branch:** `ryan/dev/hss-002-ERP-Governance-Automation`
**Tests:** 100 passing (300 assertions)
**Last updated:** 2026-02-15

---

## Phase 1: Vendor Data Standardization — ✅ COMPLETE

| Task | Summary | Commit |
|------|---------|--------|
| 1.1 | Vendor Schema Mappings — migration, model, factory, tests | d50de76 |
| 1.2 | Normalization Lookup — migration, model, seeder (37 records), tests | e9b2e57 |
| 1.3 | Brand Compliance Rules — migration, model, factory, seeder (4 brands), tests | 6e56c41 |
| 1.4 | DataStandardizationService — mapVendorRow, normalizeAttribute, validateBrandCompliance | 0acdede |
| 1.5 | VendorImportController — CSV upload, preview, import + tests | e7a59bc |
| 1.6 | Frontend — VendorImport.tsx, SchemaMappingEditor, ImportPreviewTable, vendor.ts | 38fac5f |

**Other Phase 1 commits:**
- b39e646: AppLayout navbar (Transactions + Vendor Import)
- 6942a9d: VendorSchemaMappingSeeder (acme_supply, global_parts)

**Routes:** `/vendor-import` GET, `/vendor-import/preview` POST, `/vendor-import/import` POST
**Key files:** `app/Services/DataStandardizationService.php`, `app/Http/Controllers/VendorImportController.php`

---

## Phase 2: Cross-Category Audit Engine — ✅ COMPLETE

| Task | Summary | Commit |
|------|---------|--------|
| 2.1 | ERP Simulation — products, inventory_items, product_assets, data_audit_logs + ProductCatalogSeeder (100 products w/ intentional discrepancies) | 957b810 |
| 2.2 | Price Discrepancy Watchdog — PriceDiscrepancyAudit service + RunAuditCommand | 9c7d76c |
| 2.3 | Asset Health Check — AssetHealthAudit service (URL format, alt text, staleness) | d499e5f |
| 2.4 | Categorization Audit — CategorizationAudit service (SKU prefix/category matching) | a765124 |
| 2.5 | Audit Scheduling — `audit:run all` daily at 2:00 AM | 3355d54 |
| 2.6 | Audit Dashboard — AuditController + AuditDashboard.tsx + AuditSummaryCards + AuditExceptionTable | c2e51c1 |

**Routes:** `/audits` GET, `/audits/{log}/resolve` POST
**Command:** `audit:run {type}` — supports `price_discrepancy`, `asset_health`, `categorization`, `all`
**Key files:** `app/Services/Audits/`, `app/Http/Controllers/AuditController.php`, `app/Console/Commands/RunAuditCommand.php`

**Seeded discrepancies in ProductCatalogSeeder:**
- 10% price mismatches (cost > msrp) — products 1-10
- 5% ghost inventory (qty=0, ecommerce=in_stock) — products 11-15
- 5% broken URLs (invalid format) — products 16-20
- 5% miscategorized (wrong SKU prefix) — products 1-5

---

## Phase 3: Accounting SQL Library & Reporting — ❌ NOT STARTED

### 3.1 — Saved Queries (Database)
- `[NEW]` migration: `saved_audit_queries` (name, description, category, query_template, parameters json, created_by nullable)
- `[NEW]` `app/Models/SavedAuditQuery.php`
- `[NEW]` `SavedAuditQuerySeeder.php` — pre-built queries targeting existing tables:
  - High-value outliers (transactions with amount > threshold)
  - Vendor error rate (top vendors by audit issue frequency)
  - Aging transactions (grouped by 30/60/90 days)
  - Out-of-balance (products where cost > retail)
- `[TEST]` Seeder creates expected records

**Key decisions:**
- `created_by` stays nullable (no auth system)
- Queries target existing tables: transactions, products, data_audit_logs, inventory_items

### 3.2 — Query Execution Engine (Service)
- `[NEW]` `app/Services/QueryExecutionService.php`
  - `validate()` — regex rejects anything that's not SELECT
  - `execute()` — `DB::select()` with params (PDO single-statement)
  - `export()` — CSV streamed response
- `[TEST]` SQL injection prevention, mutation rejection

**Key decisions:**
- No query builder abstraction — raw `DB::select()` is sufficient for portfolio project
- SELECT-only whitelist via regex is adequate security

### 3.3 — Query Controller + Routes
- `[NEW]` `app/Http/Controllers/QueryController.php` — index, execute (JSON), export (CSV download)
- `[MOD]` `routes/web.php` — `/queries` GET, `/queries/{query}/execute` POST, `/queries/{query}/export` POST
- `[TEST]` Feature tests

### 3.4 — Query Runner Frontend
- `[NEW]` `resources/js/types/query.ts`
- `[NEW]` `resources/js/Pages/QueryLibrary.tsx` — category tabs, query cards
- `[NEW]` `resources/js/components/QueryRunner.tsx` — param form + run button
- `[NEW]` `resources/js/components/QueryResultsTable.tsx` — dynamic columns DataTable + export button
- `[MOD]` `resources/js/components/AppLayout.tsx` — add Query Library nav link

---

## Phase 4: Real-Time Integrity Guard — ❌ NOT STARTED

### 4.1 — Integrity Alerts (Database)
- `[NEW]` migration: `integrity_alerts` (alert_type, source_module, target_module, entity_type, entity_id, details json, acknowledged_at, auto_resolved_at)
- `[NEW]` `app/Models/IntegrityAlert.php` — scopes: unacknowledged, active, critical
- `[NEW]` factory + test

### 4.2 — Feed Logs (Database)
- `[NEW]` migration: `feed_logs` (feed_source, feed_type, status, records_processed, records_failed, error_summary json, started_at, completed_at)
- `[NEW]` `app/Models/FeedLog.php`
- `[NEW]` factory + test

### 4.3 — Inventory Sync Monitor + Event
- `[NEW]` `app/Services/Integrity/InventorySyncMonitor.php` — `checkGhosting()` finds qty=0 + ecommerce=in_stock, creates alert, dispatches event
- `[NEW]` `app/Events/IntegrityAlertCreated.php` — ShouldBroadcastNow on `integrity-alerts` channel (follows TransactionCreated pattern)
- `[TEST]` Seed ghost scenario, verify alert

### 4.4 — Price Sync Guard
- `[NEW]` `app/Services/Integrity/PriceSyncGuard.php` — `checkStalePrices()` flags sync lag > threshold
- `[TEST]` Seed stale price scenario

### 4.5 — Feed Logger Service
- `[NEW]` `app/Services/Integrity/FeedLoggerService.php` — logFeedStart, logFeedComplete, getFeedHealthReport
- `[TEST]` Logging lifecycle

### 4.6 — Scheduling + Webhooks
- `[NEW]` Commands: CheckGhostingCommand, CheckPricesCommand
- `[MOD]` `routes/console.php` — ghosting every 5min, prices every 1min
- `[NEW]` `routes/api.php` — POST `/webhooks/feed-complete` (Bearer token via `FEED_WEBHOOK_SECRET` env)
- `[MOD]` `bootstrap/app.php` — register api routes

### 4.7 — Integrity Monitor Frontend
- `[NEW]` `resources/js/types/integrity.ts`
- `[NEW]` `resources/js/hooks/useIntegrityAlerts.ts` — Echo on `integrity-alerts` channel
- `[NEW]` `resources/js/Pages/IntegrityMonitor.tsx`
- `[NEW]` `resources/js/components/IntegrityAlertFeed.tsx` — live feed + acknowledge button
- `[NEW]` `resources/js/components/FeedHealthTable.tsx` — success rates, last run times
- `[NEW]` `resources/js/components/IntegrityAlertBanner.tsx` — persistent banner for critical alerts
- `[MOD]` `resources/js/components/AppLayout.tsx` — add Integrity nav link
- `[NEW]` controller + routes + test

---

## Phase 5: Navigation & Integration — ❌ NOT STARTED

**Note:** AppLayout navbar already exists (created during Phase 1). Phase 5 converts it to a sidebar layout and adds a home dashboard with aggregate stats.

### 5.1 — App Navigation (Sidebar)
- `[NEW]` `resources/js/components/AppSidebar.tsx` — sidebar nav, current page highlight, mobile collapse
- `[MOD]` `resources/js/components/AppLayout.tsx` — replace top navbar with sidebar + main content layout
- `[MOD]` all Pages — wrap in AppLayout (already done for Dashboard, VendorImport, AuditDashboard)

### 5.2 — Home Dashboard
- `[NEW]` `app/Http/Controllers/DashboardController.php` — aggregates: transactions, unresolved audits, active alerts, feed health
- `[MOD]` `routes/web.php` — `GET /` uses DashboardController
- `[MOD]` `resources/js/Pages/Dashboard.tsx` — add summary cards above transaction table
- `[TEST]` Feature test

---

## Execution Order (Remaining)

```
Phase 3: 3.1 → 3.2 → 3.3 → 3.4
Phase 4: 4.1 + 4.2 (independent) → 4.3, 4.4, 4.5 (independent) → 4.6 → 4.7
Phase 5: 5.1 → 5.2
```

One commit per numbered task. `sail artisan test` green before proceeding.

---

## Current Database Tables

| Table | Phase | Purpose |
|-------|-------|---------|
| transactions | Pre-existing | Core transaction data |
| transaction_logs | Pre-existing | Audit trail for transactions |
| vendor_schema_mappings | 1.1 | Vendor CSV → ERP column mappings |
| attribute_normalizations | 1.2 | Raw value → normalized value lookup |
| brand_compliance_rules | 1.3 | Brand-specific validation rules |
| products | 2.1 | ERP simulation — product catalog |
| inventory_items | 2.1 | ERP simulation — inventory per product |
| product_assets | 2.1 | ERP simulation — images/docs per product |
| data_audit_logs | 2.1 | Audit findings from all audit services |

## Current Routes

| Method | Path | Controller | Phase |
|--------|------|-----------|-------|
| GET | `/` | Closure | Pre-existing |
| GET | `/vendor-import` | VendorImportController@index | 1.5 |
| POST | `/vendor-import/preview` | VendorImportController@preview | 1.5 |
| POST | `/vendor-import/import` | VendorImportController@import | 1.5 |
| GET | `/audits` | AuditController@index | 2.6 |
| POST | `/audits/{log}/resolve` | AuditController@resolve | 2.6 |

## Current Navbar Links

Transactions (`/`) · Vendor Import (`/vendor-import`) · Data Audits (`/audits`)
