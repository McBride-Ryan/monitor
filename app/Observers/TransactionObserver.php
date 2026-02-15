<?php

namespace App\Observers;

use App\Models\Transaction;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $transaction->logs()->create([
            'origin' => $transaction->order_origin,
            'status' => 'success',
            'logged_at' => now(),
        ]);
    }
}
