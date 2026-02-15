# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Real-time transaction monitoring dashboard. Laravel 12 + React 19 + TypeScript + Inertia.js + PrimeReact. WebSockets via Laravel Reverb. Dockerized with Laravel Sail.

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

**Backend flow:** Transaction created → TransactionObserver auto-creates TransactionLog → TransactionCreated event broadcasts via Reverb (ShouldBroadcastNow) on `transactions` channel.

**Frontend flow:** Dashboard.tsx (Inertia page) → TransactionDashboard.tsx (layout + filter state) → useTransactions hook listens to Echo WebSocket, prepends new transactions → filtered list passed to TransactionTable, TotalSumCard, BrandSummaryCard. useConnectionStatus hook monitors WebSocket health.

**Key patterns:**
- Single Inertia route: `GET /` serves Dashboard with initial transactions
- Observer pattern for log integrity (1 Transaction = 1 TransactionLog)
- Memoized calculations for total sum and brand aggregation
- SimulateTransactions command: random 5-30s intervals, burst of 10 every 10th iteration
- Path alias: `@/*` → `resources/js/*`

**Stack:** PHP 8.2+, MySQL 9.3, Redis, Node 20, Tailwind CSS v4, PrimeReact DataTable

## Database

- `transactions`: id, timestamp, amount (decimal 10,2), description, account_type (checking/savings/credit), order_origin (Brand_1-4)
- `transaction_logs`: id, transaction_id (FK cascade), origin, status (success/failed), logged_at
- Tests use SQLite in-memory (phpunit.xml)

---

## Implementation Plan — Work Order Queue

Each phase builds on the previous. Each task is a single Claude Code prompt. Commit after each completed task.

### Notation
- `[NEW]` = create from scratch
- `[MOD]` = modify existing file
- `[TEST]` = write accompanying test

---

### Phase 1: Vendor Data Standardization Middleware

**Why first:** Clean data in = everything downstream works. This is the foundation.

**Target ERP modules:** Vendors, Product Data, Inventory

#### 1.1 — Database: Vendor Schema Mappings
- `[NEW]` `database/migrations/*_create_vendor_schema_mappings_table.php` — columns: `id`, `vendor_name` (string, indexed), `vendor_column` (string), `erp_column` (string), `transform_rule` (json nullable), `timestamps`
- `[NEW]` `app/Models/VendorSchemaMapping.php` — fillable, casts (transform_rule → array), scoped by vendor_name
- `[TEST]` Model factory + migration test

#### 1.2 — Database: Normalization Lookup
- `[NEW]` `database/migrations/*_create_attribute_normalizations_table.php` — columns: `id`, `attribute_type` (e.g. "material", "color"), `raw_value` (string, indexed), `normalized_value` (string), `timestamps`
- `[NEW]` `app/Models/AttributeNormalization.php`
- `[NEW]` `database/seeders/AttributeNormalizationSeeder.php` — seed common variants (e.g. "SS", "Stainless Steel", "SST" → "STAINLESS_STEEL")
- `[TEST]` Seeder produces expected normalizations

#### 1.3 — Database: Brand Compliance Rules
- `[NEW]` `database/migrations/*_create_brand_compliance_rules_table.php` — columns: `id`, `brand` (string, indexed), `rule_type` (string), `rule_config` (json), `is_active` (boolean default true), `timestamps`
- `[NEW]` `app/Models/BrandComplianceRule.php`
- `[TEST]` Factory + basic CRUD test

#### 1.4 — Service: DataStandardizationService
- `[NEW]` `app/Services/DataStandardizationService.php`
  - `mapVendorRow(string $vendor, array $row): array` — applies schema mappings to transform vendor CSV row → ERP columns
  - `normalizeAttribute(string $type, string $rawValue): string` — looks up normalization table, returns normalized or original
  - `validateBrandCompliance(string $brand, array $data): array` — returns array of violations
- `[TEST]` Unit tests for each method with edge cases (unknown vendor, unmapped column, no normalization match)

#### 1.5 — Backend: Vendor Import Controller + Route
- `[NEW]` `app/Http/Controllers/VendorImportController.php`
  - `index()` — return Inertia page with existing mappings
  - `preview(Request $request)` — accept CSV upload, return preview of mapped + normalized rows
  - `import(Request $request)` — run full import with validation, return results summary
- `[MOD]` `routes/web.php` — add `/vendor-import` routes
- `[TEST]` Feature tests for upload, preview, import (use CSV fixtures in `tests/fixtures/`)

#### 1.6 — Frontend: Schema Mapping UI
- `[NEW]` `resources/js/Pages/VendorImport.tsx` — Inertia page
- `[NEW]` `resources/js/components/SchemaMappingEditor.tsx` — drag-drop or select UI mapping vendor columns → ERP columns
- `[NEW]` `resources/js/components/ImportPreviewTable.tsx` — shows before/after normalization
- `[NEW]` `resources/js/types/vendor.ts` — TypeScript interfaces for mappings, import results
- `[MOD]` `resources/js/app.tsx` — register new page if needed

---

### Phase 2: Cross-Category Audit Engine

**Why second:** With standardized data flowing, we can now detect inconsistencies.

**Target ERP modules:** Product Data, Price Tags & Pricing, E-Commerce

#### 2.1 — Database: Audit Logs
- `[NEW]` `database/migrations/*_create_data_audit_logs_table.php` — columns: `id`, `audit_type` (string, indexed: "price_discrepancy", "broken_asset", "orphaned_product"), `severity` (enum: info/warning/critical), `entity_type` (string), `entity_id` (unsignedBigInteger nullable), `details` (json), `resolved_at` (timestamp nullable), `timestamps`
- `[NEW]` `app/Models/DataAuditLog.php` — fillable, casts, scopes: `unresolved()`, `bySeverity()`, `byType()`
- `[TEST]` Model + scopes test

#### 2.2 — Service: Price Discrepancy Watchdog
- `[NEW]` `app/Services/Audits/PriceDiscrepancyAudit.php`
  - `run(): int` — compares PO cost vs MSRP/Retail in pricing, logs discrepancies to data_audit_logs, returns count found
  - Configurable threshold (e.g. flag if difference > 15%)
- `[NEW]` `app/Console/Commands/RunAuditCommand.php` — `php artisan audit:run {type}` dispatches the appropriate audit
- `[TEST]` Unit test with seeded price mismatches

#### 2.3 — Service: Asset Health Check
- `[NEW]` `app/Services/Audits/AssetHealthAudit.php`
  - `run(): int` — crawls E-Commerce image URLs, flags 404s and missing alt tags
  - Batched HTTP checks (use Laravel HTTP pool)
- `[TEST]` Mock HTTP responses, verify 404 detection

#### 2.4 — Service: Categorization Audit
- `[NEW]` `app/Services/Audits/CategorizationAudit.php`
  - `run(): int` — SQL query to find orphaned products (listed in wrong category silo)
- `[TEST]` Seed cross-category orphans, verify detection

#### 2.5 — Scheduling
- `[MOD]` `routes/console.php` or `app/Console/Kernel.php` — schedule all audits daily (or configure per-audit frequency)

#### 2.6 — Frontend: Audit Dashboard
- `[NEW]` `resources/js/Pages/AuditDashboard.tsx` — Inertia page showing DataAuditLog entries
- `[NEW]` `resources/js/components/AuditExceptionTable.tsx` — PrimeReact DataTable with severity badges, filters by type/severity/resolved
- `[NEW]` `resources/js/components/AuditSummaryCards.tsx` — count by type, count by severity, trend over time
- `[NEW]` `app/Http/Controllers/AuditController.php` — index (paginated logs), resolve (mark as resolved)
- `[MOD]` `routes/web.php` — add `/audits` routes
- `[NEW]` `resources/js/types/audit.ts`

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
