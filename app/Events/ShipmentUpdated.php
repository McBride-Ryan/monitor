<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ShipmentUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public Shipment $shipment) {}

    public function broadcastOn(): array
    {
        return [new Channel('transactions')];
    }

    public function broadcastWith(): array
    {
        return ['shipment' => $this->shipment->toArray()];
    }
}
