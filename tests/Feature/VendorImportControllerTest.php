<?php

namespace Tests\Feature;

use App\Models\VendorSchemaMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VendorImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_inertia_page(): void
    {
        $response = $this->get('/vendor-import');

        $response->assertStatus(200);
    }

    public function test_preview_maps_csv_rows(): void
    {
        VendorSchemaMapping::create(['vendor_name' => 'acme_supply', 'vendor_column' => 'item_num', 'erp_column' => 'sku']);
        VendorSchemaMapping::create(['vendor_name' => 'acme_supply', 'vendor_column' => 'desc', 'erp_column' => 'name']);
        VendorSchemaMapping::create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'mat',
            'erp_column' => 'material',
            'transform_rule' => ['type' => 'uppercase'],
        ]);

        $file = new UploadedFile(
            base_path('tests/fixtures/acme_supply.csv'),
            'acme_supply.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->post('/vendor-import/preview', [
            'file' => $file,
            'vendor' => 'acme_supply',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(3, $data['preview']);
        $this->assertEquals('ACM-001', $data['preview'][0]['mapped']['sku']);
    }

    public function test_preview_requires_file_and_vendor(): void
    {
        $response = $this->post('/vendor-import/preview', []);

        $response->assertStatus(302); // validation redirect
    }

    public function test_import_returns_summary(): void
    {
        VendorSchemaMapping::create(['vendor_name' => 'global_parts', 'vendor_column' => 'PartNumber', 'erp_column' => 'sku']);
        VendorSchemaMapping::create(['vendor_name' => 'global_parts', 'vendor_column' => 'Description', 'erp_column' => 'name']);

        $file = new UploadedFile(
            base_path('tests/fixtures/global_parts.csv'),
            'global_parts.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->post('/vendor-import/import', [
            'file' => $file,
            'vendor' => 'global_parts',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['total']);
        $this->assertArrayHasKey('imported', $data);
        $this->assertArrayHasKey('skipped', $data);
    }
}
