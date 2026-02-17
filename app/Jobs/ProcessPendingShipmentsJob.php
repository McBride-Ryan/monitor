<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ProcessPendingShipmentsJob implements ShouldQueue
{
    use Queueable;

    private const CARRIERS = ['fedex', 'ups', 'usps', 'dhl'];

    public function handle(): void
    {
        // Don't pull thousands of transactions into memory
        $query = Transaction::doesntHave('shipment')->limit(200);
        $pending = $query->get();

        if ($pending->isEmpty()) {
            return;
        }

        // Ship 50–75% of pending each cycle — rest held for next batch
        $batchSize = (int) ($pending->count() * rand(50, 75) / 100);
        $batchSize = max(1, $batchSize);

        $pending->shuffle()->take($batchSize)->each(function (Transaction $transaction) {
            $carrier = self::CARRIERS[array_rand(self::CARRIERS)];

            Shipment::create([
                'transaction_id'    => $transaction->id,
                'carrier'           => $carrier,
                'tracking_number'   => $this->trackingNumber($carrier),
                'status'            => 'packing',
                'estimated_delivery' => now()->addDays(rand(2, 7)),
            ]);
        });
    }

    private function trackingNumber(string $carrier): string
    {
        return match ($carrier) {
            'fedex' => strtoupper(Str::random(12)),
            'ups'   => '1Z' . strtoupper(Str::random(16)),
            'usps'  => rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(10, 99),
            'dhl'   => rand(1000000000, 9999999999),
        };
    }
}
