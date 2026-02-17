<?php

namespace App\Observers;

use App\Events\ShipmentUpdated;
use App\Models\Shipment;

class ShipmentObserver
{
    private const LOCATIONS = [
        'packing'          => ['Warehouse District', 'Fulfillment Center A', 'Processing Hub'],
        'shipped'          => ['Memphis, TN', 'Louisville, KY', 'Chicago, IL', 'Dallas, TX'],
        'out_for_delivery' => ['Local Delivery Hub', 'Distribution Center', 'City Depot'],
        'delivered'        => ['Destination'],
        'exception'        => ['Customs Hold', 'Address Verification', 'Weather Delay Hub'],
    ];

    private const MESSAGES = [
        'packing'          => 'Label printed, package being prepared',
        'shipped'          => 'Package in transit to destination',
        'out_for_delivery' => 'Out for delivery — expected today',
        'delivered'        => 'Package delivered successfully',
        'exception'        => 'Delivery exception — carrier follow-up required',
    ];

    public function created(Shipment $shipment): void
    {
        $shipment->logs()->create([
            'status'    => $shipment->status,
            'location'  => $this->randomLocation($shipment->status),
            'message'   => self::MESSAGES[$shipment->status],
            'logged_at' => now(),
        ]);

        ShipmentUpdated::dispatch($shipment->load('logs'));
    }

    public function updated(Shipment $shipment): void
    {
        if (! $shipment->wasChanged('status')) {
            return;
        }

        $shipment->logs()->create([
            'status'    => $shipment->status,
            'location'  => $this->randomLocation($shipment->status),
            'message'   => self::MESSAGES[$shipment->status],
            'logged_at' => now(),
        ]);

        ShipmentUpdated::dispatch($shipment->load('logs'));
    }

    private function randomLocation(string $status): string
    {
        $options = self::LOCATIONS[$status] ?? ['Unknown'];
        return $options[array_rand($options)];
    }
}
