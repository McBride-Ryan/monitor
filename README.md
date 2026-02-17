
---

# Real-Time Transaction & Shipment Monitor

Real-time transaction monitoring system with async shipment tracking pipeline. Built with Laravel, Inertia SSR, React/TypeScript, PrimeReact, Laravel Reverb (WebSockets), and Laravel Horizon.

---

## Features

- **Live transaction feed** — WebSocket-pushed transactions prepend to the table in real-time
- **Async shipment pipeline** — scheduled jobs batch-create shipments and advance status every 30s
- **Shipment tracking** — packing → shipped → out for delivery → delivered (with exception simulation)
- **Filtering** — by account type, brand, and shipment status (including "unshipped")
- **Horizon dashboard** — full job queue observability at `/horizon`
- **Dark fintech UI** — deep slate + electric cyan aesthetic, PrimeReact `lara-dark-blue` theme

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Queue | Redis + Laravel Horizon |
| Frontend | React 19, TypeScript, Inertia.js |
| UI | PrimeReact, Tailwind CSS 4 |
| Real-time | Laravel Reverb, Laravel Echo |
| Dev | Vite 7, Sail (Docker), PHPUnit |

---

## Quick Start (Docker)

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

Then open five terminals:

```bash
./vendor/bin/sail artisan reverb:start                   # WebSocket server
./vendor/bin/sail artisan horizon                        # Queue worker
./vendor/bin/sail artisan schedule:work                  # Shipment scheduler (every 30s)
./vendor/bin/sail npm run dev                            # Vite HMR
./vendor/bin/sail artisan app:simulate-transactions      # Optional: generate transactions
```

Open `http://localhost` for the dashboard, `http://localhost/horizon` for queue monitoring.

---

## Quick Start (Without Docker)

```bash
composer setup   # install, key, migrate, build
php artisan reverb:start   # separate terminal
php artisan schedule:work  # separate terminal
composer dev               # server + horizon + logs + vite
```

---

## Architecture

### Transaction Pipeline (synchronous)

```
app:simulate-transactions
  → Transaction::create()
    → TransactionObserver → TransactionLog::create()     (1:1, always)
    → TransactionCreated::dispatch()
      → Reverb → frontend prepends to table
```

### Shipment Pipeline (async, every 30s)

```
Scheduler
  → ProcessPendingShipmentsJob (Redis queue)
      ships 50–75% of unshipped transactions per cycle
      → Shipment::create(status=packing)
        → ShipmentObserver → ShipmentLog::create()
        → ShipmentUpdated → Reverb → frontend patches row

  → AdvanceShipmentStatusJob (Redis queue)
      picks up to 15 in-flight shipments
      62% advance  |  3% exception  |  35% hold
      → shipment.update(status=next)
        → ShipmentObserver → ShipmentLog::create()
        → ShipmentUpdated → Reverb → frontend patches row
```

Status progression: `packing → shipped → out_for_delivery → delivered`

---

## Database Schema

```
transactions        id, timestamp, amount, description, account_type, order_origin
transaction_logs    id, transaction_id (FK), origin, status, logged_at          [1:1]

shipments           id, transaction_id (FK UNIQUE), carrier, tracking_number,
                    status, estimated_delivery                                   [1:1 with transaction]
shipment_logs       id, shipment_id (FK), status, location, message, logged_at  [1:many with shipment]
```

Indexes: `transactions(timestamp, account_type, order_origin)`, `shipments(status)`, `shipment_logs(shipment_id)`

---

## Key Files

```
app/Models/
  Transaction.php              hasMany logs, hasOne shipment
  TransactionLog.php
  Shipment.php                 belongsTo transaction, hasMany logs
  ShipmentLog.php

app/Observers/
  TransactionObserver.php      creates TransactionLog on created
  ShipmentObserver.php         creates ShipmentLog + broadcasts on created/updated

app/Events/
  TransactionCreated.php       ShouldBroadcastNow → transactions channel
  ShipmentUpdated.php          ShouldBroadcastNow → transactions channel

app/Jobs/
  ProcessPendingShipmentsJob.php   batch shipment creation (50-75% per cycle)
  AdvanceShipmentStatusJob.php     status progression (up to 15 per cycle)

app/Console/Commands/
  SimulateTransactions.php     generates transactions with burst mode

routes/
  web.php                      Inertia Dashboard route, all filters, eager loads
  console.php                  Schedule::job(...)->everyThirtySeconds() × 2

resources/js/
  types/transaction.ts         Transaction, Shipment, ShipmentLog, FilterState types
  hooks/useTransactions.ts     TransactionCreated + ShipmentUpdated WebSocket listeners
  hooks/useConnectionStatus.ts Reverb connection state
  components/
    TransactionDashboard.tsx   layout, notification bar, filter state
    TransactionTable.tsx        DataTable + shipment column (carrier + status badge)
    FilterSidebar.tsx           account type + brand + shipment status filters
    TotalSumCard.tsx            memoized total
    BrandSummaryCard.tsx        memoized brand breakdown with progress bars
    ConnectionStatusBanner.tsx  live pulse dot indicator

config/horizon.php             Horizon queue configuration
```

---

## Filtering

| Filter | Values | Backend behavior |
|---|---|---|
| Account type | All / checking / savings / credit | `whereIn` or omit for all three |
| Brand | Brand_1–4 (multi-select) | `whereIn('order_origin', ...)` |
| Shipment status | All / unshipped / packing / shipped / out_for_delivery / delivered / exception | `doesntHave` or `whereHas` |

---

## Components Summary

| Component | Purpose |
|---|---|
| `app.tsx` | Inertia client entry, PrimeReact lara-dark-blue theme |
| `ssr.tsx` | Inertia SSR entry |
| `echo.ts` | Laravel Echo / Reverb client |
| `useTransactions.ts` | WebSocket listener: new transactions + shipment status patches |
| `useConnectionStatus.ts` | Reverb connection health |
| `Dashboard.tsx` | Inertia page, passes props to dashboard |
| `TransactionDashboard.tsx` | Layout, filters, notification bar (no Toast) |
| `TransactionTable.tsx` | PrimeReact DataTable, paginated, sortable, shipment column |
| `FilterSidebar.tsx` | Account type + brand + shipment status filters |
| `TotalSumCard.tsx` | Memoized sum with cyan accent |
| `BrandSummaryCard.tsx` | Memoized brand totals with progress bars |
| `ConnectionStatusBanner.tsx` | Live/connecting/disconnected pill with pulse animation |
