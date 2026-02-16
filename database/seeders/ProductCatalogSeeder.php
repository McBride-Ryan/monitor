<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductAsset;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Electronics', 'Furniture', 'Clothing', 'Tools', 'Home & Garden'];
        $skuPrefixes = [
            'Electronics' => 'EL',
            'Furniture' => 'FR',
            'Clothing' => 'CL',
            'Tools' => 'TL',
            'Home & Garden' => 'HG',
        ];

        for ($i = 1; $i <= 100; $i++) {
            $category = fake()->randomElement($categories);
            $correctPrefix = $skuPrefixes[$category];

            // 5% miscategorized (wrong SKU prefix for category)
            $isMiscategorized = $i <= 5;
            $skuPrefix = $isMiscategorized
                ? fake()->randomElement(array_diff(array_values($skuPrefixes), [$correctPrefix]))
                : $correctPrefix;

            $cost = fake()->randomFloat(2, 10, 500);

            // 10% price discrepancy (cost > msrp)
            $hasPriceDiscrepancy = $i <= 10;
            if ($hasPriceDiscrepancy) {
                $msrp = $cost * 0.8; // cost is higher than msrp
                $retailPrice = $cost * 0.9;
            } else {
                $msrp = $cost * fake()->randomFloat(2, 1.5, 3.0);
                $retailPrice = $cost * fake()->randomFloat(2, 1.3, 2.5);
            }

            $product = Product::create([
                'sku' => strtoupper($skuPrefix.'-'.str_pad($i, 4, '0', STR_PAD_LEFT)),
                'name' => fake()->words(3, true),
                'category' => $category,
                'brand' => fake()->randomElement(['Brand_1', 'Brand_2', 'Brand_3', 'Brand_4']),
                'vendor_id' => fake()->numberBetween(1, 10),
                'cost' => $cost,
                'msrp' => $msrp,
                'retail_price' => $retailPrice,
                'status' => 'active',
            ]);

            // Inventory item
            $qtyOnHand = fake()->numberBetween(0, 1000);

            // 5% ghost inventory (qty=0 but ecommerce=in_stock)
            $isGhostInventory = $i > 10 && $i <= 15;
            if ($isGhostInventory) {
                $qtyOnHand = 0;
                $ecommerceStatus = 'in_stock';
            } else {
                $ecommerceStatus = $qtyOnHand > 0 ? 'in_stock' : 'out_of_stock';
            }

            InventoryItem::create([
                'product_id' => $product->id,
                'warehouse_location' => fake()->randomElement(['A-1', 'B-2', 'C-3', 'D-4', 'E-5']),
                'qty_on_hand' => $qtyOnHand,
                'qty_committed' => fake()->numberBetween(0, 50),
                'ecommerce_status' => $ecommerceStatus,
                'last_synced_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
            ]);

            // Product asset
            // 5% broken URLs (invalid format)
            $hasBrokenUrl = $i > 15 && $i <= 20;
            $url = $hasBrokenUrl
                ? 'broken-url-'.fake()->word()
                : fake()->imageUrl(640, 480, 'products', true);

            ProductAsset::create([
                'product_id' => $product->id,
                'asset_type' => 'image',
                'url' => $url,
                'alt_text' => fake()->optional(0.8)->sentence(6),
                'is_active' => true,
                'last_checked_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            ]);
        }
    }
}
