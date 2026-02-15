<?php

namespace Tests\Unit;

use App\Models\DataAuditLog;
use App\Models\Product;
use App\Services\Audits\PriceDiscrepancyAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceDiscrepancyAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_cost_exceeds_msrp(): void
    {
        Product::factory()->create([
            'sku' => 'TEST-001',
            'cost' => 100.00,
            'msrp' => 80.00,
            'retail_price' => 120.00,
        ]);

        $audit = new PriceDiscrepancyAudit();
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'price_discrepancy',
            'severity' => 'critical',
            'entity_type' => 'Product',
        ]);

        $log = DataAuditLog::first();
        $this->assertStringContainsString('Cost exceeds MSRP', $log->details['message']);
        $this->assertEquals('TEST-001', $log->details['sku']);
    }

    public function test_detects_cost_exceeds_retail_price(): void
    {
        Product::factory()->create([
            'sku' => 'TEST-002',
            'cost' => 100.00,
            'msrp' => 120.00,
            'retail_price' => 90.00,
        ]);

        $audit = new PriceDiscrepancyAudit();
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'price_discrepancy',
            'severity' => 'critical',
        ]);

        $log = DataAuditLog::where('entity_type', 'Product')->first();
        $this->assertStringContainsString('Cost exceeds retail price', $log->details['message']);
    }

    public function test_detects_low_margin_below_threshold(): void
    {
        Product::factory()->create([
            'sku' => 'TEST-003',
            'cost' => 100.00,
            'msrp' => 150.00,
            'retail_price' => 105.00, // 5% margin, below 15% threshold
        ]);

        $audit = new PriceDiscrepancyAudit(0.15);
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'price_discrepancy',
            'severity' => 'warning',
        ]);

        $log = DataAuditLog::where('severity', 'warning')->first();
        $this->assertStringContainsString('Margin below threshold', $log->details['message']);
    }

    public function test_ignores_products_with_null_prices(): void
    {
        Product::factory()->create([
            'cost' => null,
            'msrp' => 100.00,
            'retail_price' => 120.00,
        ]);

        Product::factory()->create([
            'cost' => 100.00,
            'msrp' => null,
            'retail_price' => 120.00,
        ]);

        $audit = new PriceDiscrepancyAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
        $this->assertEquals(0, DataAuditLog::count());
    }

    public function test_does_not_flag_valid_pricing(): void
    {
        Product::factory()->create([
            'cost' => 100.00,
            'msrp' => 200.00,
            'retail_price' => 150.00, // 50% margin
        ]);

        $audit = new PriceDiscrepancyAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
        $this->assertEquals(0, DataAuditLog::count());
    }

    public function test_returns_total_count_of_issues_found(): void
    {
        // Cost > MSRP
        Product::factory()->create([
            'cost' => 100.00,
            'msrp' => 80.00,
            'retail_price' => 120.00,
        ]);

        // Cost > Retail
        Product::factory()->create([
            'cost' => 100.00,
            'msrp' => 150.00,
            'retail_price' => 90.00,
        ]);

        // Low margin
        Product::factory()->create([
            'cost' => 100.00,
            'msrp' => 150.00,
            'retail_price' => 110.00,
        ]);

        $audit = new PriceDiscrepancyAudit();
        $count = $audit->run();

        $this->assertEquals(3, $count);
        $this->assertEquals(3, DataAuditLog::count());
    }

    public function test_custom_threshold_changes_detection(): void
    {
        Product::factory()->create([
            'cost' => 100.00,
            'msrp' => 150.00,
            'retail_price' => 125.00, // 25% margin
        ]);

        // With 15% threshold, should not flag
        $audit1 = new PriceDiscrepancyAudit(0.15);
        $count1 = $audit1->run();
        $this->assertEquals(0, $count1);

        DataAuditLog::truncate();

        // With 30% threshold, should flag
        $audit2 = new PriceDiscrepancyAudit(0.30);
        $count2 = $audit2->run();
        $this->assertEquals(1, $count2);
    }
}
