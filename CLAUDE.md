# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

ERP Governance Automation platform built on a real-time transaction monitoring dashboard. Laravel 12 + React 19 + TypeScript + Inertia.js + PrimeReact. WebSockets via Laravel Reverb. Dockerized with Laravel Sail. Portfolio/demo project — no real ERP backend.

## Commands

All commands run through Sail (Docker):

```bash
# Setup
cp .env.example .env && ./vendor/bin/sail up -d && ./vendor/bin/sail artisan migrate --seed

# Dev (3 terminals)
./vendor/bin/sail artisan reverb:start
./vendor/bin/sail npm run dev
./vendor/bin/sail artisan app:simulate-transactions

# Tests
./vendor/bin/sail artisan test                    # all tests
./vendor/bin/sail artisan test --filter=TestName  # single test

# Build & tools
./vendor/bin/sail npm run build
./vendor/bin/sail artisan pint                    # PHP code style
./vendor/bin/sail artisan tinker                  # REPL
```

App runs at http://localhost. Reverb WebSocket on port 8080. Vite dev server on port 5173.

## Architecture

**Transaction monitoring:** Transaction created → TransactionObserver auto-creates TransactionLog → TransactionCreated event broadcasts via Reverb (ShouldBroadcastNow) on `transactions` channel. Dashboard.tsx → TransactionDashboard.tsx → useTransactions hook (Echo WebSocket).

**Vendor import pipeline:** CSV upload → DataStandardizationService (mapVendorRow → normalizeAttribute → validateBrandCompliance) → preview/import. VendorImportController handles upload, preview, import.

**Audit engine:** 3 audit services in `app/Services/Audits/` — PriceDiscrepancyAudit, AssetHealthAudit, CategorizationAudit. All implement `run(): int`, return issue count, write to `data_audit_logs`. `audit:run {type}` artisan command. Scheduled daily at 2:00 AM.

**Shared layout:** AppLayout.tsx wraps all pages with navbar (Transactions, Vendor Import, Data Audits).

**Key patterns:**
- Observer pattern for log integrity (1 Transaction = 1 TransactionLog)
- Service classes for business logic, thin controllers
- PrimeReact DataTable for all tables
- Path alias: `@/*` → `resources/js/*`
- SimulateTransactions command: random 5-30s intervals, burst of 10 every 10th iteration
- ProductCatalogSeeder: 100 products w/ deterministic discrepancies by index (1-10 price, 11-15 ghost, 16-20 broken URLs, 1-5 miscategorized)

**Stack:** PHP 8.2+, MySQL 9.3, Redis, Node 20, Tailwind CSS v4, PrimeReact DataTable

## Database

- `transactions`: id, timestamp, amount, description, account_type, order_origin
- `transaction_logs`: id, transaction_id (FK), origin, status, logged_at
- `vendor_schema_mappings`: vendor_name, vendor_column, erp_column, transform_rule (json)
- `attribute_normalizations`: attribute_type, raw_value, normalized_value
- `brand_compliance_rules`: brand, rule_type, rule_config (json), is_active
- `products`: sku, name, category, brand, vendor_id, cost, msrp, retail_price, status
- `inventory_items`: product_id (FK), warehouse_location, qty_on_hand, qty_committed, ecommerce_status, last_synced_at
- `product_assets`: product_id (FK), asset_type, url, alt_text, is_active, last_checked_at
- `data_audit_logs`: audit_type, severity, entity_type, entity_id, details (json), resolved_at
- Tests use SQLite in-memory (phpunit.xml)

## Routes

| Method | Path | Controller | Phase |
|--------|------|-----------|-------|
| GET | `/` | Closure | Pre-existing |
| GET | `/vendor-import` | VendorImportController@index | 1.5 |
| POST | `/vendor-import/preview` | VendorImportController@preview | 1.5 |
| POST | `/vendor-import/import` | VendorImportController@import | 1.5 |
| GET | `/audits` | AuditController@index | 2.6 |
| POST | `/audits/{log}/resolve` | AuditController@resolve | 2.6 |

## Status

- **Tests:** 100 passing (300 assertions)
- **Phase 1:** Vendor Data Standardization — COMPLETE
- **Phase 2:** Cross-Category Audit Engine — COMPLETE
- **Phase 3-5:** Not started. See `completed_tasks.md` for detailed remaining task specs.

---

## Implementation Plan — Work Order Queue

Each phase builds on the previous. Each task is a single Claude Code prompt. Commit after each completed task.

### Notation
- `[NEW]` = create from scratch
- `[MOD]` = modify existing file
- `[TEST]` = write accompanying test

---

### Phase 1: Vendor Data Standardization — ✅ COMPLETE
See `completed_tasks.md` for commit history and details.

### Phase 2: Cross-Category Audit Engine — ✅ COMPLETE
See `completed_tasks.md` for commit history and details.

---

### Phase 3: Accounting SQL Library & Reporting

**Why third:** Builds on audit infrastructure. Gives the accountant self-service query tools.

**Target ERP modules:** General Ledger, AR, AP, Purchase Orders

#### 3.1 — Database: Saved Queries
- `[NEW]` `database/migrations/*_create_saved_audit_queries_table.php` — columns: `id`, `name` (string), `description` (text nullable), `category` (string: "gl", "ap", "ar", "po"), `query_template` (text), `parameters` (json nullable), `created_by` (unsignedBigInteger nullable), `timestamps`
- `[NEW]` `app/Models/SavedAuditQuery.php`
- `[NEW]` `database/seeders/SavedAuditQuerySeeder.php` — pre-built queries:
  - **Out-of-Balance Monitor:** flag GL entries where debits ≠ credits
  - **PO-to-Invoice Three-Way Match:** PO qty vs inventory received vs AP invoice amount
  - **Vendor Error Rate:** top 10 vendors by feed error frequency
  - **Aging AR:** receivables past 30/60/90 days
- `[TEST]` Seeder creates expected records

#### 3.2 — Service: Query Execution Engine
- `[NEW]` `app/Services/QueryExecutionService.php`
  - `execute(SavedAuditQuery $query, array $params = []): array` — safely runs parameterized query, returns results
  - `validate(string $sql): bool` — whitelist SELECT only, reject mutations
  - `export(array $results, string $format): StreamedResponse` — CSV/Excel export
- `[TEST]` SQL injection prevention, mutation rejection, valid execution

#### 3.3 — Backend: Query Controller + Routes
- `[NEW]` `app/Http/Controllers/QueryController.php`
  - `index()` — list saved queries by category
  - `execute(Request $request, SavedAuditQuery $query)` — run with params, return JSON results
  - `export(Request $request, SavedAuditQuery $query)` — download results
- `[MOD]` `routes/web.php` — add `/queries` routes
- `[TEST]` Feature tests

#### 3.4 — Frontend: Query Runner
- `[NEW]` `resources/js/Pages/QueryLibrary.tsx` — browse queries by category
- `[NEW]` `resources/js/components/QueryRunner.tsx` — select query, fill params, execute, view results in DataTable
- `[NEW]` `resources/js/components/QueryResultsTable.tsx` — dynamic columns from query results, export button
- `[NEW]` `resources/js/types/query.ts`

---

### Phase 4: Real-Time Integrity Guard

**Why last:** Leverages existing Reverb/broadcasting infrastructure. Most complex, most dependent on prior phases.

**Target ERP modules:** Inventory, POS, Digital Unity, ADC

#### 4.1 — Database: Integrity Alerts
- `[NEW]` `database/migrations/*_create_integrity_alerts_table.php` — columns: `id`, `alert_type` (string: "inventory_ghost", "price_sync_lag", "feed_failure"), `source_module` (string), `target_module` (string), `entity_type` (string), `entity_id` (unsignedBigInteger nullable), `details` (json), `acknowledged_at` (timestamp nullable), `auto_resolved_at` (timestamp nullable), `timestamps`
- `[NEW]` `app/Models/IntegrityAlert.php`
- `[TEST]` Model test

#### 4.2 — Database: Feed Logs
- `[NEW]` `database/migrations/*_create_feed_logs_table.php` — columns: `id`, `feed_source` (string: "digital_unity", "adc", "vendor_ftp"), `feed_type` (string: "xml", "api", "ftp"), `status` (string: "success", "partial", "failed"), `records_processed` (integer), `records_failed` (integer), `error_summary` (json nullable), `started_at`, `completed_at`, `timestamps`
- `[NEW]` `app/Models/FeedLog.php`
- `[TEST]` Model test

#### 4.3 — Service: Inventory Sync Monitor
- `[NEW]` `app/Services/Integrity/InventorySyncMonitor.php`
  - `checkGhosting(): Collection` — finds items at qty 0 in Inventory but "In Stock" on E-Commerce
  - Dispatches IntegrityAlert + broadcasts via Reverb
- `[NEW]` `app/Events/IntegrityAlertCreated.php` — ShouldBroadcastNow on `integrity-alerts` channel
- `[TEST]` Seed ghost inventory scenario, verify alert creation

#### 4.4 — Service: Price Sync Guard
- `[NEW]` `app/Services/Integrity/PriceSyncGuard.php`
  - `checkStalePrices(int $thresholdMinutes = 5): Collection` — finds prices changed in Pricing but not yet reflected in POS
  - Creates IntegrityAlert if threshold exceeded
- `[TEST]` Seed stale price scenario

#### 4.5 — Service: Feed Logger Middleware
- `[NEW]` `app/Services/Integrity/FeedLoggerService.php`
  - `logFeedStart(string $source, string $type): FeedLog`
  - `logFeedComplete(FeedLog $log, int $processed, int $failed, ?array $errors): FeedLog`
  - `getFeedHealthReport(string $source, int $days = 7): array` — success rate, avg records, failure patterns
- `[TEST]` Unit tests for logging lifecycle

#### 4.6 — Scheduling + Webhooks
- `[MOD]` `routes/console.php` — schedule InventorySyncMonitor every 5 min, PriceSyncGuard every 1 min
- `[NEW]` `routes/api.php` — webhook endpoints for external feed systems to trigger FeedLoggerService
- `[MOD]` `bootstrap/app.php` — register API routes if not already

#### 4.7 — Frontend: Integrity Monitor
- `[NEW]` `resources/js/Pages/IntegrityMonitor.tsx` — real-time alert dashboard
- `[NEW]` `resources/js/components/IntegrityAlertFeed.tsx` — live-updating feed via Echo (same pattern as transaction dashboard)
- `[NEW]` `resources/js/components/FeedHealthTable.tsx` — feed success rates, last run times
- `[NEW]` `resources/js/components/IntegrityAlertBanner.tsx` — persistent banner for critical unacknowledged alerts
- `[NEW]` `resources/js/hooks/useIntegrityAlerts.ts` — Echo listener for `integrity-alerts` channel
- `[NEW]` `resources/js/types/integrity.ts`
- `[MOD]` `resources/js/components/ConnectionStatusBanner.tsx` — extend to show integrity channel status

---

### Phase 5: Navigation & Integration

#### 5.1 — App Navigation
- `[NEW]` `resources/js/components/AppSidebar.tsx` — sidebar nav linking all dashboards: Transactions, Vendor Import, Audits, Query Library, Integrity Monitor
- `[MOD]` `resources/js/Pages/Dashboard.tsx` — integrate sidebar layout
- `[MOD]` all new Pages — consistent layout with sidebar

#### 5.2 — Home Dashboard
- `[MOD]` `resources/js/Pages/Dashboard.tsx` — add summary cards: unresolved audit count, active integrity alerts, recent feed health, top vendor errors
- `[NEW]` `app/Http/Controllers/DashboardController.php` — aggregate stats from all modules for home page
- `[MOD]` `routes/web.php` — update `/` route to use DashboardController

---

## Working Conventions

- **One task per commit.** Each numbered task (1.1, 1.2, etc.) = one atomic commit.
- **Test before moving on.** Run `sail artisan test` after each task. Green before proceeding.
- **Follow existing patterns.** New models follow Transaction/TransactionLog conventions (fillable, casts, factory). New events follow TransactionCreated (ShouldBroadcastNow). New pages follow Dashboard.tsx (Inertia page structure).
- **PrimeReact for all tables.** Use PrimeReact DataTable consistently.
- **TypeScript strict.** All new `.ts`/`.tsx` files must have proper interfaces, no `any`.
- **Service classes for business logic.** Controllers stay thin — delegate to services.
- **Migrations are append-only.** Never edit an existing migration. Create new ones to alter tables.
