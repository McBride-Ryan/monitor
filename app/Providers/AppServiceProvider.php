<?php

namespace App\Providers;

use App\Models\Shipment;
use App\Models\Transaction;
use App\Observers\ShipmentObserver;
use App\Observers\TransactionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class);
        Shipment::observe(ShipmentObserver::class);
    }
}
