<?php

namespace App\Console\Commands;

use App\Events\TransactionCreated;
use App\Models\Transaction;
use Illuminate\Console\Command;

class SimulateTransactions extends Command
{
    protected $signature = 'app:simulate-transactions';
    protected $description = 'Simulate real-time transaction creation';

    public function handle(): void
    {
        $this->info('Simulating transactions... (Ctrl+C to stop)');

        $iteration = 0;

        while (true) {
            $iteration++;

            if ($iteration % 10 === 0) {
                $this->info("Burst: creating 10 transactions");
                for ($i = 0; $i < 10; $i++) {
                    $this->createAndBroadcast();
                }
            } else {
                $this->createAndBroadcast();
            }

            $sleep = rand(5, 30);
            $this->line("Next in {$sleep}s...");
            sleep($sleep);
        }
    }

    private function createAndBroadcast(): void
    {
        $transaction = Transaction::factory()->create([
            'timestamp' => now(),
        ]);

        TransactionCreated::dispatch($transaction);

        $this->info("Created txn #{$transaction->id}: \${$transaction->amount} ({$transaction->order_origin})");
    }
}
