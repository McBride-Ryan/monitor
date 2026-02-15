<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public Transaction $transaction)
    {
        $this->transaction->load('logs');
    }

    public function broadcastOn(): array
    {
        return [new Channel('transactions')];
    }

    public function broadcastWith(): array
    {
        return ['transaction' => $this->transaction->toArray()];
    }
}
