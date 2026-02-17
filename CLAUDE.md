# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Real-time transaction monitoring system with async shipment tracking. Laravel + Inertia SSR + React/TypeScript + PrimeReact + Laravel Reverb WebSockets + Laravel Horizon.

## Commands

### Development
```bash
# With Docker (recommended)
./vendor/bin/sail up -d
./vendor/bin/sail artisan reverb:start       # terminal 1
./vendor/bin/sail artisan horizon            # terminal 2 (queue worker + job monitoring)
./vendor/bin/sail artisan schedule:work      # terminal 3 (runs shipment jobs every 30s)
./vendor/bin/sail npm run dev                # terminal 4
./vendor/bin/sail artisan app:simulate-transactions  # terminal 5 (optional)

# Without Docker
composer dev  # runs server, horizon, logs, vite concurrently
php artisan reverb:start    # separate terminal
php artisan schedule:work   # separate terminal
```

### Setup
```bash
# With Docker
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed

# Without Docker
composer setup  # installs deps, generates key, migrates, builds assets
```

### Testing
```bash
composer test  # or: php artisan test
```

### Database
```bash
php artisan migrate
php artisan migrate --seed
php artisan app:simulate-transactions  # generates test data + broadcasts
```

### Build
```bash
npm run build  # production build with SSR
npm run dev    # dev server with HMR
```

### Custom Slash Commands

`/performance-optimization [file-or-path]` — analyzes code for DB query issues (N+1, missing indexes, unbounded queries), algorithm efficiency (O(n²) loops, linear scans), memory management (unbounded accumulation, stale closures), and caching opportunities (Redis, useMemo, HTTP cache headers). Outputs a severity-ranked summary table with before/after fixes for high-severity findings.

## Architecture

### Backend Pattern: Observer + Event Broadcasting

**Transactions:**
1. **Transaction created** → `TransactionObserver::created()` fires
2. **Observer** auto-creates `TransactionLog` entry (1:1 relationship)
3. **TransactionCreated** event dispatches with `ShouldBroadcastNow`
4. **Reverb** broadcasts to `transactions` channel

Key: Every Transaction MUST have exactly one TransactionLog. Observer enforces this.

**Shipments (async pipeline):**
1. **Scheduler** fires `ProcessPendingShipmentsJob` every 30s → queued to Redis
2. **Job** picks 50–75% of unshipped transactions (rest held for next batch — simulates delays)
3. **Shipment::create()** → `ShipmentObserver::created()` → `ShipmentLog::create()` + `ShipmentUpdated` broadcast
4. **Scheduler** also fires `AdvanceShipmentStatusJob` every 30s
5. **Job** picks up to 15 in-flight shipments, rolls: 62% advance status, 3% exception, 35% no change
6. **Status change** → `ShipmentObserver::updated()` → new `ShipmentLog` + `ShipmentUpdated` broadcast

Key: `shipments.transaction_id` has UNIQUE constraint — prevents race-condition duplicates.

### Queue / Horizon

- **Queue driver**: Redis (already in Docker stack)
- **Worker**: Laravel Horizon (`php artisan horizon`) — replaces `queue:work`
- **Dashboard**: `http://localhost/horizon` — job throughput, failures, retry UI
- **Scheduled jobs**: `routes/console.php` via `Schedule::job(...)->everyThirtySeconds()`

### Frontend Pattern: Inertia SSR + Real-Time Merging

- **Initial load**: `routes/web.php` → `Inertia::render('Dashboard')` with eager-loaded transactions + shipments
- **SSR**: `resources/js/ssr.tsx` renders server-side for SEO/performance
- **Real-time (transactions)**: `useTransactions` hook listens to Reverb, prepends new txns to state
- **Real-time (shipments)**: same hook listens for `ShipmentUpdated`, patches matching transaction's shipment in state
- **Memoization**: `TotalSumCard` and `BrandSummaryCard` use `useMemo` for expensive calculations during burst loads

### Filtering Rules

**CRITICAL**: Account type filter with "All" selected (value: null) MUST return ALL account types: checking, savings, and credit.

- Backend `routes/web.php` defaults to `whereIn('account_type', ['checking', 'savings', 'credit'])` when no `account_types` param or null/empty value
- Frontend FilterSidebar Dropdown uses `optionLabel="label"` and `optionValue="value"` to properly extract values
- When `account_type` is null, the query param is omitted entirely to trigger default behavior

**Shipment status filter:**
- `shipment_status=unshipped` → `doesntHave('shipment')` (no shipment row yet)
- `shipment_status=packing|shipped|out_for_delivery|delivered|exception` → `whereHas('shipment', ...)`
- `shipment_status` absent/null → no filter applied

### Data Flow

```
SimulateTransactions command
  → Transaction::factory()->create()
    → TransactionObserver::created()
      → TransactionLog::create()
    → TransactionCreated::dispatch()
      → Reverb broadcast → frontend prepends to state

Scheduler (every 30s)
  → ProcessPendingShipmentsJob (Redis queue)
    → Shipment::create() for 50-75% of unshipped
      → ShipmentObserver::created()
        → ShipmentLog::create()
        → ShipmentUpdated::dispatch()
          → Reverb broadcast → frontend patches tx.shipment in state
  → AdvanceShipmentStatusJob (Redis queue)
    → shipment.update(status=next) for up to 15 in-flight
      → ShipmentObserver::updated()
        → ShipmentLog::create()
        → ShipmentUpdated::dispatch()
          → Reverb broadcast → frontend patches tx.shipment in state
```

### Key Files

- **Models**: `app/Models/Transaction.php`, `TransactionLog.php`, `Shipment.php`, `ShipmentLog.php`
- **Observers**: `app/Observers/TransactionObserver.php`, `ShipmentObserver.php` (registered in `AppServiceProvider`)
- **Events**: `app/Events/TransactionCreated.php`, `ShipmentUpdated.php` (both `ShouldBroadcastNow` on `transactions` channel)
- **Jobs**: `app/Jobs/ProcessPendingShipmentsJob.php`, `AdvanceShipmentStatusJob.php`
- **Simulator**: `app/Console/Commands/SimulateTransactions.php` (bursts every 10th iteration)
- **Scheduler**: `routes/console.php` (both shipment jobs, `everyThirtySeconds()`)
- **Frontend hooks**: `resources/js/hooks/useTransactions.ts` (handles both `TransactionCreated` + `ShipmentUpdated`), `useConnectionStatus.ts`
- **Echo config**: `resources/js/echo.ts` (Reverb WebSocket client)
- **Horizon config**: `config/horizon.php`

### Database Schema

**transactions**: id, timestamp, amount, description, account_type (checking/savings/credit), order_origin (Brand_1-4)
**transactions index**: timestamp, account_type, order_origin

**transaction_logs**: id, transaction_id (FK), origin, status, logged_at

**shipments**: id, transaction_id (FK UNIQUE), carrier (fedex/ups/usps/dhl), tracking_number (UNIQUE), status (packing/shipped/out_for_delivery/delivered/exception), estimated_delivery, timestamps
**shipments index**: status

**shipment_logs**: id, shipment_id (FK), status, location, message, logged_at, timestamps
**shipment_logs index**: shipment_id

### Shipment Status Progression

```
packing → shipped → out_for_delivery → delivered
                                     ↘ exception (3% chance at any step)
```

Each status change appends a new `ShipmentLog` row (1:many — tracking history, unlike TransactionLog which is 1:1).

### React Components Structure

- `Dashboard.tsx` (Inertia page) → passes initial data
- `TransactionDashboard.tsx` (layout) → orchestrates filters, cards, table; inline notification bar (no PrimeReact Toast)
- `TransactionTable.tsx` (PrimeReact DataTable) → pagination, sorting, shipment column with carrier + status badge
- `FilterSidebar.tsx` → account_type dropdown + brand multiselect + shipment status dropdown
- `TotalSumCard.tsx`, `BrandSummaryCard.tsx` → memoized aggregations
- `ConnectionStatusBanner.tsx` → Reverb health indicator with CSS pulse animation

### TypeScript Types

Defined in `resources/js/types/transaction.ts`:
- `Transaction` — includes `shipment?: Shipment | null`
- `Shipment` — carrier, tracking_number, status, estimated_delivery, logs?
- `ShipmentLog` — status, location, message, logged_at
- `FilterState` — includes `shipment_status: string | null`
- `ShipmentStatus` type: `'packing' | 'shipped' | 'out_for_delivery' | 'delivered' | 'exception'`
- `Carrier` type: `'fedex' | 'ups' | 'usps' | 'dhl'`

### UI Theme

Dark fintech aesthetic (`lara-dark-blue` PrimeReact theme):
- Background: `#0f172a` (deep slate)
- Surfaces: `#1e293b` (medium slate)
- Accent: `#06b6d4` (electric cyan) — amounts, progress bars, top border on TotalSumCard
- Notification bar: fixed top, cyan bg, slides in via `animate-slide-down`
- Connection dot: CSS `animate-pulse-dot` when live

## Docker

- **Services**: laravel.test (PHP 8.5 + Node), mysql, redis
- **Reverb**: port 8080 (exposed in compose.yaml)
- **Sail**: Laravel's Docker wrapper (`./vendor/bin/sail`)

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Queue**: Redis + Laravel Horizon
- **Frontend**: React 19, TypeScript, Inertia.js, PrimeReact, Tailwind CSS 4
- **Real-time**: Laravel Reverb (WebSockets), Laravel Echo, Pusher.js
- **Dev tools**: Vite 7, Laravel Pint (linting), PHPUnit, Sail (Docker)
