<?php

namespace App\Jobs;

use App\Models\Shipment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AdvanceShipmentStatusJob implements ShouldQueue
{
    use Queueable;

    private const TRANSITIONS = [
        'packing'          => 'shipped',
        'shipped'          => 'out_for_delivery',
        'out_for_delivery' => 'delivered',
    ];

    public function handle(): void
    {
        Shipment::whereIn('status', ['packing', 'shipped', 'out_for_delivery'])
            ->inRandomOrder()
            ->limit(15)
            ->get()
            ->each(function (Shipment $shipment) {
                $roll = rand(1, 100);

                if ($roll <= 3) {
                    $shipment->update(['status' => 'exception']);
                } elseif ($roll <= 65) {
                    $next = self::TRANSITIONS[$shipment->status] ?? null;
                    if ($next) {
                        $shipment->update(['status' => $next]);
                    }
                }
                // 35%: no change — stays in current status
            });
    }
}
