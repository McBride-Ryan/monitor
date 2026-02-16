<?php

namespace App\Services\Audits;

use App\Models\DataAuditLog;
use App\Models\Product;

class PriceDiscrepancyAudit
{
    private float $threshold;

    public function __construct(float $threshold = 0.15)
    {
        $this->threshold = $threshold;
    }

    public function run(): int
    {
        $count = 0;

        // Check for cost > MSRP violations
        $costExceedsMsrp = Product::whereColumn('cost', '>', 'msrp')
            ->whereNotNull('cost')
            ->whereNotNull('msrp')
            ->get();

        foreach ($costExceedsMsrp as $product) {
            DataAuditLog::create([
                'audit_type' => 'price_discrepancy',
                'severity' => 'critical',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'details' => [
                    'message' => 'Cost exceeds MSRP',
                    'sku' => $product->sku,
                    'cost' => $product->cost,
                    'msrp' => $product->msrp,
                    'difference' => $product->cost - $product->msrp,
                ],
            ]);
            $count++;
        }

        // Check for cost > retail_price violations
        $costExceedsRetail = Product::whereColumn('cost', '>', 'retail_price')
            ->whereNotNull('cost')
            ->whereNotNull('retail_price')
            ->get();

        foreach ($costExceedsRetail as $product) {
            DataAuditLog::create([
                'audit_type' => 'price_discrepancy',
                'severity' => 'critical',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'details' => [
                    'message' => 'Cost exceeds retail price',
                    'sku' => $product->sku,
                    'cost' => $product->cost,
                    'retail_price' => $product->retail_price,
                    'difference' => $product->cost - $product->retail_price,
                ],
            ]);
            $count++;
        }

        // Check for margin threshold violations (retail too close to cost)
        $lowMargin = Product::whereNotNull('cost')
            ->whereNotNull('retail_price')
            ->whereColumn('cost', '<', 'retail_price')
            ->get()
            ->filter(function ($product) {
                $margin = ($product->retail_price - $product->cost) / $product->cost;
                return $margin < $this->threshold;
            });

        foreach ($lowMargin as $product) {
            $margin = ($product->retail_price - $product->cost) / $product->cost;
            DataAuditLog::create([
                'audit_type' => 'price_discrepancy',
                'severity' => 'warning',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'details' => [
                    'message' => 'Margin below threshold',
                    'sku' => $product->sku,
                    'cost' => $product->cost,
                    'retail_price' => $product->retail_price,
                    'margin_percent' => round($margin * 100, 2),
                    'threshold_percent' => $this->threshold * 100,
                ],
            ]);
            $count++;
        }

        return $count;
    }
}
