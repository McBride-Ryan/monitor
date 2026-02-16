# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Real-time transaction monitoring system. Laravel + Inertia SSR + React/TypeScript + PrimeReact + Laravel Reverb WebSockets.

## Commands

### Development
```bash
# With Docker (recommended)
./vendor/bin/sail up -d
./vendor/bin/sail artisan reverb:start  # terminal 1
./vendor/bin/sail npm run dev           # terminal 2
./vendor/bin/sail artisan app:simulate-transactions  # terminal 3 (optional)

# Without Docker
composer dev  # runs server, queue, logs, vite concurrently
php artisan reverb:start  # separate terminal
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

## Architecture

### Backend Pattern: Observer + Event Broadcasting

1. **Transaction created** → `TransactionObserver::created()` fires
2. **Observer** auto-creates `TransactionLog` entry (1:1 relationship)
3. **TransactionCreated** event dispatches with `ShouldBroadcastNow`
4. **Reverb** broadcasts to `transactions` channel

Key: Every Transaction MUST have exactly one TransactionLog. Observer enforces this.

### Frontend Pattern: Inertia SSR + Real-Time Merging

- **Initial load**: `routes/web.php` → `Inertia::render('Dashboard')` with eager-loaded transactions
- **SSR**: `resources/js/ssr.tsx` renders server-side for SEO/performance
- **Real-time**: `useTransactions` hook listens to Reverb, prepends new txns to state
- **Memoization**: `TotalSumCard` and `BrandSummaryCard` use `useMemo` for expensive calculations during burst loads

### Filtering Rules

**CRITICAL**: Account type filter with "All" selected (value: null) MUST return ALL account types: checking, savings, and credit.

- Backend `routes/web.php` defaults to `whereIn('account_type', ['checking', 'savings', 'credit'])` when no `account_types` param or null/empty value
- Frontend FilterSidebar Dropdown uses `optionLabel="label"` and `optionValue="value"` to properly extract values
- When `account_type` is null, the query param is omitted entirely to trigger default behavior

### Data Flow

```
SimulateTransactions command
  → Transaction::factory()->create()
    → TransactionObserver::created()
      → TransactionLog::create()
    → TransactionCreated::dispatch()
      → Reverb broadcast
        → Frontend echo.channel('transactions').listen()
          → useTransactions prepends to state
            → PrimeReact DataTable rerenders
```

### Key Files

- **Models**: `app/Models/Transaction.php`, `TransactionLog.php` (with relationship)
- **Observer**: `app/Observers/TransactionObserver.php` (registered in `AppServiceProvider`)
- **Event**: `app/Events/TransactionCreated.php` (implements `ShouldBroadcastNow`)
- **Simulator**: `app/Console/Commands/SimulateTransactions.php` (bursts every 10th iteration)
- **Frontend hooks**: `resources/js/hooks/useTransactions.ts`, `useConnectionStatus.ts`
- **Echo config**: `resources/js/echo.ts` (Reverb WebSocket client)

### Database Schema

**transactions**: id, timestamp, amount, description, account_type (checking/savings/credit), order_origin (Brand_1-4)
**transactions index**: timestamp, account_type, order_origin

**transaction_logs**: id, transaction_id (FK), origin, status, logged_at

### React Components Structure

- `Dashboard.tsx` (Inertia page) → passes initial data
- `TransactionDashboard.tsx` (layout) → orchestrates filters, cards, table
- `TransactionTable.tsx` (PrimeReact DataTable) → pagination, sorting
- `FilterSidebar.tsx` → account_type dropdown + brand multiselect
- `TotalSumCard.tsx`, `BrandSummaryCard.tsx` → memoized aggregations
- `ConnectionStatusBanner.tsx` → Reverb health indicator

### TypeScript Types

Defined in `resources/js/types/transaction.ts`. Transaction type includes nested `logs[]` relationship.

## Docker

- **Services**: laravel.test (PHP 8.5 + Node), mysql, redis
- **Reverb**: port 8080 (exposed in compose.yaml)
- **Sail**: Laravel's Docker wrapper (`./vendor/bin/sail`)

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: React 19, TypeScript, Inertia.js, PrimeReact, Tailwind CSS 4
- **Real-time**: Laravel Reverb (WebSockets), Laravel Echo, Pusher.js
- **Dev tools**: Vite 7, Laravel Pint (linting), PHPUnit, Sail (Docker)
