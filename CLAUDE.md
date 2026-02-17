# CLAUDE.md

Real-time transaction monitoring: Laravel 12 + Inertia SSR + React 19/TypeScript + PrimeReact + Reverb WebSockets + Horizon.

## Commands

```bash
# Docker (recommended)
./vendor/bin/sail up -d
./vendor/bin/sail artisan reverb:start       # WS server
./vendor/bin/sail artisan horizon            # queue worker
./vendor/bin/sail artisan schedule:work      # shipment jobs every 30s
./vendor/bin/sail npm run dev
./vendor/bin/sail artisan app:simulate-transactions  # optional test data

# Without Docker
composer dev                 # server + horizon + logs + vite concurrently
php artisan reverb:start     # separate terminal
php artisan schedule:work    # separate terminal
composer setup               # first-time: install, key, migrate, build
composer test
```

**Custom slash command**: `/performance-optimization [path]` — ranks perf issues (N+1, indexes, O(n²), memory, caching) with before/after fixes.

## Architecture

### Backend: Observer + Event Broadcasting

**Transactions**: `Transaction::create()` → `TransactionObserver::created()` → `TransactionLog::create()` (1:1) + `TransactionCreated` broadcast (`ShouldBroadcastNow`, `transactions` channel).

**Shipments (async)**: Scheduler fires two jobs every 30s (`routes/console.php`):
- `ProcessPendingShipmentsJob` — ships 50–75% of unshipped → `ShipmentObserver::created()` → `ShipmentLog` + `ShipmentUpdated` broadcast
- `AdvanceShipmentStatusJob` — advances ≤15 in-flight (62% progress / 3% exception / 35% no-op) → `ShipmentObserver::updated()` → new `ShipmentLog` + broadcast

`shipments.transaction_id` UNIQUE prevents race duplicates. Queue: Redis + Horizon (`/horizon` dashboard).

Status progression: `packing → shipped → out_for_delivery → delivered` (3% chance → `exception` at any step)

### Frontend: Inertia SSR + Real-Time Merging + On-Demand Detail Loading

- Initial load: lean payload (no `shipment_logs`) via `Inertia::render('Dashboard')`
- `useTransactions` hook: prepends new txns on `TransactionCreated`; deep-merges `ShipmentUpdated` scalar fields into `paginatedData`; updates `detailsCache[id]` if already loaded
- Row expand → `fetchDetails(id)` → `GET /api/transactions/{id}/details` → `DetailsCache = Record<number, ShipmentLog[] | 'loading'>` sentinel prevents duplicate requests; cache cleared on filter/page change
- `TotalSumCard`, `BrandSummaryCard`: `useMemo` for burst-load aggregations

### Filtering Rules

**CRITICAL**: `account_type` null → `whereIn('account_type', ['checking','savings','credit'])` (all types). Never return empty set.

Shipment filter: `unshipped` → `doesntHave('shipment')`; named statuses → `whereHas('shipment', ...)`; null → no filter applied.

### Key Files

- **Models**: `app/Models/{Transaction,TransactionLog,Shipment,ShipmentLog}.php`
- **Observers**: `app/Observers/{Transaction,Shipment}Observer.php` (registered in `AppServiceProvider`)
- **Events**: `app/Events/{TransactionCreated,ShipmentUpdated}.php` (both on `transactions` channel; `ShipmentUpdated` queued via Redis)
- **Jobs**: `app/Jobs/{ProcessPendingShipments,AdvanceShipmentStatus}Job.php`
- **Scheduler**: `routes/console.php` | **Routes**: `routes/web.php`
- **Frontend hook**: `resources/js/hooks/useTransactions.ts` | **WS client**: `resources/js/echo.ts`
- **Types**: `resources/js/types/transaction.ts` | **Horizon**: `config/horizon.php`

### Database Schema

- **transactions**: id, timestamp, amount, description, account_type (checking/savings/credit), order_origin (Brand_1–4); idx: timestamp, account_type, order_origin
- **transaction_logs**: id, transaction_id FK, origin, status, logged_at — 1:1 with transaction
- **shipments**: id, transaction_id FK UNIQUE, carrier (fedex/ups/usps/dhl), tracking_number UNIQUE, status, estimated_delivery; idx: status
- **shipment_logs**: id, shipment_id FK, status, location, message, logged_at; idx: shipment_id — 1:many (full history)

### React Components

`Dashboard.tsx` → `TransactionDashboard.tsx` (filters, cards, table, notification bar; threads `detailsCache`+`fetchDetails`) → `TransactionTable.tsx` (DataTable, row expand, `RowExpansion`+`TrackingTimeline`, spinner on `'loading'`)

Also: `FilterSidebar.tsx`, `TotalSumCard.tsx`, `BrandSummaryCard.tsx`, `ConnectionStatusBanner.tsx`

### TypeScript Types (`resources/js/types/transaction.ts`)

- `Transaction`: `shipment?: Shipment | null` (no logs in page payload)
- `Shipment`: carrier, tracking_number, status, estimated_delivery, `logs?` (WS/API response only)
- `ShipmentLog`: status, location, message, logged_at
- `FilterState`: includes `shipment_status: string | null`
- `DetailsCache = Record<number, ShipmentLog[] | 'loading'>` (exported from `useTransactions.ts`)

### UI Theme

`lara-dark-blue` PrimeReact: bg `#0f172a`, surfaces `#1e293b`, accent `#06b6d4` (cyan). Notification bar: fixed top, cyan bg, `animate-slide-down`. Connection dot: `animate-pulse-dot`.

## Docker

Services: `laravel.test` (PHP 8.5 + Node), `mysql`, `redis`. Reverb on port 8080. Sail = `./vendor/bin/sail`.
