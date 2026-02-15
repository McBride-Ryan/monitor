<?php

namespace App\Services\Audits;

use App\Models\DataAuditLog;
use App\Models\Product;

class CategorizationAudit
{
    private array $categoryPrefixes = [
        'Electronics' => 'EL',
        'Furniture' => 'FR',
        'Clothing' => 'CL',
        'Tools' => 'TL',
        'Home & Garden' => 'HG',
    ];

    public function run(): int
    {
        $count = 0;

        // Check for null or empty categories
        $nullCategories = Product::where(function ($query) {
            $query->whereNull('category')
                ->orWhere('category', '');
        })->get();

        foreach ($nullCategories as $product) {
            DataAuditLog::create([
                'audit_type' => 'orphaned_product',
                'severity' => 'warning',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'details' => [
                    'message' => 'Product has no category assigned',
                    'sku' => $product->sku,
                    'name' => $product->name,
                ],
            ]);
            $count++;
        }

        // Check for SKU prefix / category mismatches
        $categorizedProducts = Product::whereNotNull('category')
            ->where('category', '!=', '')
            ->get();

        foreach ($categorizedProducts as $product) {
            $expectedPrefix = $this->categoryPrefixes[$product->category] ?? null;

            if ($expectedPrefix === null) {
                // Unknown category
                DataAuditLog::create([
                    'audit_type' => 'orphaned_product',
                    'severity' => 'warning',
                    'entity_type' => 'Product',
                    'entity_id' => $product->id,
                    'details' => [
                        'message' => 'Product category not recognized',
                        'sku' => $product->sku,
                        'category' => $product->category,
                        'valid_categories' => array_keys($this->categoryPrefixes),
                    ],
                ]);
                $count++;
                continue;
            }

            // Extract SKU prefix (assuming format: PREFIX-####)
            $skuPrefix = strtoupper(substr($product->sku, 0, 2));

            if ($skuPrefix !== $expectedPrefix) {
                DataAuditLog::create([
                    'audit_type' => 'orphaned_product',
                    'severity' => 'critical',
                    'entity_type' => 'Product',
                    'entity_id' => $product->id,
                    'details' => [
                        'message' => 'SKU prefix does not match category',
                        'sku' => $product->sku,
                        'sku_prefix' => $skuPrefix,
                        'category' => $product->category,
                        'expected_prefix' => $expectedPrefix,
                    ],
                ]);
                $count++;
            }
        }

        return $count;
    }
}
