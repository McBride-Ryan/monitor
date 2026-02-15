
---

# PROJECT SPECIFICATION: High-Volume Real-Time Transaction & Log Monitor

## 🤖 Agent Environment Setup
Initialize the workspace with these specialized tools before generating code:

1.  **Claude Code Marketplace:** `/plugin marketplace add anthropics/claude-code`
2.  **Design System Plugin:** `/plugin install frontend-design@claude-code-plugins`
3.  **UI Directive:** Use `frontend-design` to ensure the PrimeReact implementation is responsive across Mobile, Tablet, and Desktop, utilizing Tailwind CSS for fluid spacing.

---

## 1. Database Architecture & Models

### A. Transactions Table (Updated Schema)
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->timestamp('timestamp');
    $table->decimal('amount', 10, 2);
    $table->string('description');
    $table->string('account_type'); // checking, savings, credit
    $table->string('order_origin'); // Brand_1, Brand_2, Brand_3, Brand_4
    $table->timestamps();
});
```

### B. Transaction Logs Table
Create a `TransactionLog` model to audit every incoming entry, specifically tracking ETL backoffice origins.
- **Fields:** `id`, `transaction_id` (constrained), `origin`, `status` (success/failed), `logged_at`.

### C. Seeding & Logic
- **Factory:** Generate data where `order_origin` is randomly assigned from `['Brand_1', 'Brand_2', 'Brand_3', 'Brand_4']`.
- **Observer:** Create a `TransactionObserver`. Whenever a `Transaction` is created, automatically create a corresponding `TransactionLog` entry.

---

## 2. Real-Time Engine (Laravel Reverb)
- **WebSockets:** Implement Laravel Reverb for real-time broadcasting.
- **Event:** `TransactionCreated` (implements `ShouldBroadcastNow`).
- **Simulator:** Create `app:simulate-transactions`.
    - Loop and generate a transaction every 5–30 seconds.
    - Randomize the `order_origin` brand for each entry.
    - **High-Volume Burst:** Every 10th iteration, trigger 10 transactions at once to stress-test the Log model and Frontend list.

---

## 3. Frontend: React, TypeScript, & SSR
- **Inertia SSR:** Configure `ssr.tsx` to render the initial state server-side.
- **PrimeReact DataTable:**
    - **Live Merging:** Use a custom hook to manage the state. New WebSocket data must be prepended to the top of the table.
    - **Filtering:** Implement a dropdown for `account_type` and a multi-select for `order_origin` (Brands).
    - **Memoization:** Use `useMemo` for the "Total Sum" calculation and the "Brand Summary" card to ensure UI fluidity during high-volume updates.
- **Resilience UI:**
    - Display a **Connection Status Indicator**.
    - If Reverb fails, show: *"Live stream interrupted. Offline data remains interactive. Reconnecting..."*

---

## 4. Dockerization (Seamless Setup)
To ensure a "one-command" setup, use a `docker-compose.yml` based on **Laravel Sail**, customized for Reverb.

**Services to include:**
1.  **`laravel.test`**: PHP 8.2/8.3, Node.js 20 (for Vite/SSR).
2.  **`mysql`**: Persistence.
3.  **`redis`**: For broadcast caching.
4.  **`reverb`**: The dedicated WebSocket port (8080).
5.  **`selenium`**: (Optional) For automated testing.

**Setup Command for the User:**
```bash
cp .env.example .env && ./vendor/bin/sail up -d && ./vendor/bin/sail artisan migrate --seed
```

---

## 5. Verification Protocols
- **Log Integrity:** Verify that for every 1 Transaction created, 1 Log entry exists in the `transaction_logs` table.
- **Concurrency Check:** Ensure the "Total Amount" sum updates correctly when a burst of 5+ transactions arrives via WebSockets.
- **Responsive Check:**
    - **Mobile:** Table should enable horizontal scroll or transform into "cards" for the transaction list.
    - **Desktop:** Full data grid with Brand filtering sidebar.

---

# 🚀 Agentic Execution Plan

### Phase 1: Containerization & Backend
1. Generate `docker-compose.yml` and `.env` configured for Reverb.
2. Create Migrations for `transactions` and `transaction_logs`.
3. Build Models, Observers, and Factories.
4. Setup the `app:simulate-transactions` command.

### Phase 2: Real-Time Layer
1. Configure `broadcasting.php` for Reverb.
2. Implement the `TransactionCreated` event.
3. Test the broadcast using `php artisan tinker`.

### Phase 3: Frontend & Design
1. Initialize Inertia with TypeScript and SSR enabled.
2. Install PrimeReact and Tailwind CSS.
3. Use the `frontend-design` plugin to generate the `TransactionDashboard`.
4. Implement the WebSocket listener (Laravel Echo) and the memoized state logic.

### Phase 4: Error Handling & Refinement
1. Add the "Connection Monitor" banner.
2. Implement total sum calculations (memoized).
3. Ensure strict TypeScript types for all Brand and Transaction objects.

---