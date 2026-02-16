<?php

namespace Tests\Feature;

use App\Models\DataAuditLog;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductAsset;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_migration_creates_table(): void
    {
        $this->assertTrue(\Schema::hasTable('products'));
        $this->assertTrue(\Schema::hasColumn('products', 'sku'));
        $this->assertTrue(\Schema::hasColumn('products', 'cost'));
        $this->assertTrue(\Schema::hasColumn('products', 'msrp'));
    }

    public function test_inventory_items_migration_creates_table(): void
    {
        $this->assertTrue(\Schema::hasTable('inventory_items'));
        $this->assertTrue(\Schema::hasColumn('inventory_items', 'product_id'));
        $this->assertTrue(\Schema::hasColumn('inventory_items', 'qty_on_hand'));
        $this->assertTrue(\Schema::hasColumn('inventory_items', 'ecommerce_status'));
    }

    public function test_product_assets_migration_creates_table(): void
    {
        $this->assertTrue(\Schema::hasTable('product_assets'));
        $this->assertTrue(\Schema::hasColumn('product_assets', 'product_id'));
        $this->assertTrue(\Schema::hasColumn('product_assets', 'url'));
        $this->assertTrue(\Schema::hasColumn('product_assets', 'is_active'));
    }

    public function test_data_audit_logs_migration_creates_table(): void
    {
        $this->assertTrue(\Schema::hasTable('data_audit_logs'));
        $this->assertTrue(\Schema::hasColumn('data_audit_logs', 'audit_type'));
        $this->assertTrue(\Schema::hasColumn('data_audit_logs', 'severity'));
        $this->assertTrue(\Schema::hasColumn('data_audit_logs', 'resolved_at'));
    }

    public function test_product_factory_creates_product(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => $product->sku,
        ]);
    }

    public function test_product_has_inventory_items_relationship(): void
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $product->inventoryItems);
        $this->assertCount(1, $product->inventoryItems);
    }

    public function test_product_has_assets_relationship(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $product->assets);
        $this->assertCount(1, $product->assets);
    }

    public function test_inventory_item_belongs_to_product(): void
    {
        $item = InventoryItem::factory()->create();

        $this->assertInstanceOf(Product::class, $item->product);
    }

    public function test_product_asset_belongs_to_product(): void
    {
        $asset = ProductAsset::factory()->create();

        $this->assertInstanceOf(Product::class, $asset->product);
    }

    public function test_data_audit_log_factory_creates_log(): void
    {
        $log = DataAuditLog::factory()->create();

        $this->assertDatabaseHas('data_audit_logs', [
            'id' => $log->id,
            'audit_type' => $log->audit_type,
        ]);
    }

    public function test_data_audit_log_unresolved_scope(): void
    {
        DataAuditLog::factory()->create(['resolved_at' => null]);
        DataAuditLog::factory()->create(['resolved_at' => now()]);

        $unresolved = DataAuditLog::unresolved()->get();

        $this->assertCount(1, $unresolved);
    }

    public function test_data_audit_log_by_severity_scope(): void
    {
        DataAuditLog::factory()->create(['severity' => 'critical']);
        DataAuditLog::factory()->create(['severity' => 'warning']);

        $critical = DataAuditLog::bySeverity('critical')->get();

        $this->assertCount(1, $critical);
        $this->assertEquals('critical', $critical->first()->severity);
    }

    public function test_data_audit_log_by_type_scope(): void
    {
        DataAuditLog::factory()->create(['audit_type' => 'price_discrepancy']);
        DataAuditLog::factory()->create(['audit_type' => 'broken_asset']);

        $priceIssues = DataAuditLog::byType('price_discrepancy')->get();

        $this->assertCount(1, $priceIssues);
        $this->assertEquals('price_discrepancy', $priceIssues->first()->audit_type);
    }

    public function test_product_catalog_seeder_creates_100_products(): void
    {
        $seeder = new ProductCatalogSeeder();
        $seeder->run();

        $this->assertEquals(100, Product::count());
        $this->assertEquals(100, InventoryItem::count());
        $this->assertEquals(100, ProductAsset::count());
    }

    public function test_product_catalog_seeder_creates_price_discrepancies(): void
    {
        $seeder = new ProductCatalogSeeder();
        $seeder->run();

        $discrepancies = Product::whereColumn('cost', '>', 'msrp')->count();

        $this->assertGreaterThanOrEqual(8, $discrepancies); // ~10% = 10 products
    }

    public function test_product_catalog_seeder_creates_ghost_inventory(): void
    {
        $seeder = new ProductCatalogSeeder();
        $seeder->run();

        $ghosts = InventoryItem::where('qty_on_hand', 0)
            ->where('ecommerce_status', 'in_stock')
            ->count();

        $this->assertGreaterThanOrEqual(3, $ghosts); // ~5% = 5 items
    }

    public function test_product_catalog_seeder_creates_broken_urls(): void
    {
        $seeder = new ProductCatalogSeeder();
        $seeder->run();

        $broken = ProductAsset::where('url', 'like', 'broken-url-%')->count();

        $this->assertGreaterThanOrEqual(3, $broken); // ~5% = 5 assets
    }
}
