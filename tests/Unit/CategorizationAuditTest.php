<?php

namespace Tests\Unit;

use App\Models\DataAuditLog;
use App\Models\Product;
use App\Services\Audits\CategorizationAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_null_category(): void
    {
        Product::factory()->create([
            'sku' => 'EL-0001',
            'category' => null,
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'orphaned_product',
            'severity' => 'warning',
            'entity_type' => 'Product',
        ]);

        $log = DataAuditLog::first();
        $this->assertStringContainsString('no category assigned', $log->details['message']);
    }

    public function test_detects_empty_category(): void
    {
        Product::factory()->create([
            'sku' => 'FR-0001',
            'category' => '',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(1, $count);
        $log = DataAuditLog::first();
        $this->assertStringContainsString('no category assigned', $log->details['message']);
    }

    public function test_detects_sku_prefix_mismatch(): void
    {
        Product::factory()->create([
            'sku' => 'FR-0001',
            'category' => 'Electronics', // Should be EL prefix
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'orphaned_product',
            'severity' => 'critical',
        ]);

        $log = DataAuditLog::where('severity', 'critical')->first();
        $this->assertStringContainsString('SKU prefix does not match category', $log->details['message']);
        $this->assertEquals('FR', $log->details['sku_prefix']);
        $this->assertEquals('Electronics', $log->details['category']);
        $this->assertEquals('EL', $log->details['expected_prefix']);
    }

    public function test_detects_unrecognized_category(): void
    {
        Product::factory()->create([
            'sku' => 'XX-0001',
            'category' => 'Unknown Category',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(1, $count);
        $log = DataAuditLog::first();
        $this->assertStringContainsString('category not recognized', $log->details['message']);
        $this->assertEquals('Unknown Category', $log->details['category']);
        $this->assertArrayHasKey('valid_categories', $log->details);
    }

    public function test_does_not_flag_correct_categorization(): void
    {
        Product::factory()->create([
            'sku' => 'EL-0001',
            'category' => 'Electronics',
        ]);

        Product::factory()->create([
            'sku' => 'FR-0002',
            'category' => 'Furniture',
        ]);

        Product::factory()->create([
            'sku' => 'CL-0003',
            'category' => 'Clothing',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
        $this->assertEquals(0, DataAuditLog::count());
    }

    public function test_validates_all_known_categories(): void
    {
        // Test all valid category/prefix combinations
        $validCombinations = [
            ['sku' => 'EL-0001', 'category' => 'Electronics'],
            ['sku' => 'FR-0002', 'category' => 'Furniture'],
            ['sku' => 'CL-0003', 'category' => 'Clothing'],
            ['sku' => 'TL-0004', 'category' => 'Tools'],
            ['sku' => 'HG-0005', 'category' => 'Home & Garden'],
        ];

        foreach ($validCombinations as $data) {
            Product::factory()->create($data);
        }

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
    }

    public function test_sku_prefix_check_is_case_insensitive(): void
    {
        Product::factory()->create([
            'sku' => 'el-0001', // lowercase
            'category' => 'Electronics',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
    }

    public function test_returns_total_count_of_issues(): void
    {
        // Null category
        Product::factory()->create([
            'sku' => 'EL-0001',
            'category' => null,
        ]);

        // SKU mismatch
        Product::factory()->create([
            'sku' => 'FR-0002',
            'category' => 'Electronics',
        ]);

        // Unrecognized category
        Product::factory()->create([
            'sku' => 'XX-0003',
            'category' => 'BadCategory',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(3, $count);
        $this->assertEquals(3, DataAuditLog::count());
    }

    public function test_multiple_mismatches_creates_separate_logs(): void
    {
        Product::factory()->create([
            'sku' => 'FR-0001',
            'category' => 'Electronics',
        ]);

        Product::factory()->create([
            'sku' => 'EL-0002',
            'category' => 'Furniture',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        $this->assertEquals(2, $count);

        $logs = DataAuditLog::where('severity', 'critical')->get();
        $this->assertCount(2, $logs);

        $skus = $logs->pluck('details.sku')->toArray();
        $this->assertContains('FR-0001', $skus);
        $this->assertContains('EL-0002', $skus);
    }

    public function test_handles_sku_without_standard_format(): void
    {
        Product::factory()->create([
            'sku' => 'TOOLSET',
            'category' => 'Tools',
        ]);

        $audit = new CategorizationAudit();
        $count = $audit->run();

        // Should flag because TO != TL
        $this->assertEquals(1, $count);
    }
}
