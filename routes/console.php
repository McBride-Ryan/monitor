<?php

use App\Jobs\AdvanceShipmentStatusJob;
use App\Jobs\ProcessPendingShipmentsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessPendingShipmentsJob)->everyThirtySeconds();
Schedule::job(new AdvanceShipmentStatusJob)->everyThirtySeconds();
