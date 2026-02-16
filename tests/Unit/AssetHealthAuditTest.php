<?php

namespace Tests\Unit;

use App\Models\DataAuditLog;
use App\Models\Product;
use App\Models\ProductAsset;
use App\Services\Audits\AssetHealthAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetHealthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_invalid_url_format(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'broken-url-test',
            'is_active' => true,
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'broken_asset',
            'severity' => 'critical',
            'entity_type' => 'ProductAsset',
        ]);

        $log = DataAuditLog::where('severity', 'critical')->first();
        $this->assertStringContainsString('Invalid URL format', $log->details['message']);
        $this->assertEquals('broken-url-test', $log->details['url']);
    }

    public function test_detects_missing_alt_text_for_images(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'asset_type' => 'image',
            'url' => 'https://example.com/image.jpg',
            'alt_text' => null,
            'is_active' => true,
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'broken_asset',
            'severity' => 'warning',
        ]);

        $log = DataAuditLog::where('severity', 'warning')->first();
        $this->assertStringContainsString('Missing alt text', $log->details['message']);
    }

    public function test_detects_empty_alt_text_for_images(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'asset_type' => 'image',
            'url' => 'https://example.com/image.jpg',
            'alt_text' => '',
            'is_active' => true,
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $log = DataAuditLog::where('severity', 'warning')->first();
        $this->assertStringContainsString('Missing alt text', $log->details['message']);
    }

    public function test_ignores_missing_alt_text_for_non_images(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'asset_type' => 'video',
            'url' => 'https://example.com/video.mp4',
            'alt_text' => null,
            'is_active' => true,
            'last_checked_at' => now(),
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        // Should not create warning for missing alt text on videos
        $warningLogs = DataAuditLog::where('severity', 'warning')->get();
        $this->assertCount(0, $warningLogs);
    }

    public function test_detects_stale_last_checked_at(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/image.jpg',
            'last_checked_at' => now()->subDays(40),
            'is_active' => true,
        ]);

        $audit = new AssetHealthAudit(30);
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('data_audit_logs', [
            'audit_type' => 'broken_asset',
            'severity' => 'info',
        ]);

        $log = DataAuditLog::where('severity', 'info')->first();
        $this->assertStringContainsString('not checked recently', $log->details['message']);
        $this->assertEquals(30, $log->details['threshold_days']);
    }

    public function test_detects_null_last_checked_at(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/image.jpg',
            'last_checked_at' => null,
            'is_active' => true,
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertGreaterThanOrEqual(1, $count);
        $log = DataAuditLog::where('severity', 'info')->first();
        $this->assertStringContainsString('not checked recently', $log->details['message']);
    }

    public function test_ignores_inactive_assets(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'broken-url',
            'alt_text' => null,
            'is_active' => false,
            'last_checked_at' => null,
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
        $this->assertEquals(0, DataAuditLog::count());
    }

    public function test_does_not_flag_healthy_assets(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/image.jpg',
            'alt_text' => 'Product image',
            'is_active' => true,
            'last_checked_at' => now()->subDays(5),
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertEquals(0, $count);
        $this->assertEquals(0, DataAuditLog::count());
    }

    public function test_accepts_valid_http_and_https_urls(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'http://example.com/image.jpg',
            'alt_text' => 'Test',
            'is_active' => true,
            'last_checked_at' => now(),
        ]);

        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/image.jpg',
            'alt_text' => 'Test',
            'is_active' => true,
            'last_checked_at' => now(),
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        // Should not flag valid URLs as broken
        $criticalLogs = DataAuditLog::where('severity', 'critical')->count();
        $this->assertEquals(0, $criticalLogs);
    }

    public function test_custom_stale_threshold(): void
    {
        $product = Product::factory()->create();
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/image.jpg',
            'last_checked_at' => now()->subDays(20),
            'is_active' => true,
        ]);

        // With 30 day threshold, should not flag
        $audit1 = new AssetHealthAudit(30);
        $count1 = $audit1->run();
        $this->assertEquals(0, $count1);

        DataAuditLog::truncate();

        // With 10 day threshold, should flag
        $audit2 = new AssetHealthAudit(10);
        $count2 = $audit2->run();
        $this->assertEquals(1, $count2);
    }

    public function test_returns_total_count_of_issues(): void
    {
        $product = Product::factory()->create();

        // Invalid URL
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'broken-url',
            'is_active' => true,
            'last_checked_at' => now(),
        ]);

        // Missing alt text
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'asset_type' => 'image',
            'url' => 'https://example.com/img.jpg',
            'alt_text' => null,
            'is_active' => true,
            'last_checked_at' => now(),
        ]);

        // Stale check
        ProductAsset::factory()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/img2.jpg',
            'last_checked_at' => null,
            'is_active' => true,
        ]);

        $audit = new AssetHealthAudit();
        $count = $audit->run();

        $this->assertEquals(3, $count);
        $this->assertEquals(3, DataAuditLog::count());
    }
}
