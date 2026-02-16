<?php

namespace Tests\Feature;

use App\Models\VendorSchemaMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorSchemaMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_table(): void
    {
        $this->assertTrue(
            \Schema::hasTable('vendor_schema_mappings')
        );
    }

    public function test_factory_creates_mapping(): void
    {
        $mapping = VendorSchemaMapping::factory()->create();

        $this->assertDatabaseHas('vendor_schema_mappings', [
            'id' => $mapping->id,
        ]);
    }

    public function test_transform_rule_cast_to_array(): void
    {
        $mapping = VendorSchemaMapping::factory()->create([
            'transform_rule' => ['type' => 'uppercase'],
        ]);

        $mapping->refresh();
        $this->assertIsArray($mapping->transform_rule);
        $this->assertEquals('uppercase', $mapping->transform_rule['type']);
    }

    public function test_scope_for_vendor(): void
    {
        VendorSchemaMapping::factory()->create(['vendor_name' => 'acme_supply', 'vendor_column' => 'col_a']);
        VendorSchemaMapping::factory()->create(['vendor_name' => 'acme_supply', 'vendor_column' => 'col_b']);
        VendorSchemaMapping::factory()->create(['vendor_name' => 'global_parts', 'vendor_column' => 'col_c']);

        $acme = VendorSchemaMapping::forVendor('acme_supply')->get();

        $this->assertCount(2, $acme);
    }

    public function test_unique_constraint_on_vendor_and_column(): void
    {
        VendorSchemaMapping::factory()->create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'sku',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        VendorSchemaMapping::factory()->create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'sku',
        ]);
    }
}
