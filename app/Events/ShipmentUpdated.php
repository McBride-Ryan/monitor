<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class ShipmentUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels, InteractsWithSockets;

    public function __construct(public Shipment $shipment) {}

    public function broadcastOn(): array
    {
        return [new Channel('transactions')];
    }

    public function broadcastWith(): array
    {
        // Ensure logs are included for the dashboard timeline
        return [
            'shipment' => $this->shipment->loadMissing('logs')->toArray()
        ];
    }
}
