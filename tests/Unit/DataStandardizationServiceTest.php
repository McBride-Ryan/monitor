<?php

namespace Tests\Unit;

use App\Models\AttributeNormalization;
use App\Models\BrandComplianceRule;
use App\Models\VendorSchemaMapping;
use App\Services\DataStandardizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataStandardizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataStandardizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataStandardizationService;
    }

    // -- mapVendorRow --

    public function test_map_vendor_row_applies_mappings(): void
    {
        VendorSchemaMapping::create(['vendor_name' => 'acme', 'vendor_column' => 'item_num', 'erp_column' => 'sku']);
        VendorSchemaMapping::create(['vendor_name' => 'acme', 'vendor_column' => 'desc', 'erp_column' => 'name']);

        $result = $this->service->mapVendorRow('acme', [
            'item_num' => 'ABC-123',
            'desc' => 'Widget',
        ]);

        $this->assertEquals(['sku' => 'ABC-123', 'name' => 'Widget'], $result);
    }

    public function test_map_vendor_row_applies_uppercase_transform(): void
    {
        VendorSchemaMapping::create([
            'vendor_name' => 'acme',
            'vendor_column' => 'material',
            'erp_column' => 'material',
            'transform_rule' => ['type' => 'uppercase'],
        ]);

        $result = $this->service->mapVendorRow('acme', ['material' => 'steel']);

        $this->assertEquals('STEEL', $result['material']);
    }

    public function test_map_vendor_row_applies_trim_transform(): void
    {
        VendorSchemaMapping::create([
            'vendor_name' => 'acme',
            'vendor_column' => 'name',
            'erp_column' => 'name',
            'transform_rule' => ['type' => 'trim'],
        ]);

        $result = $this->service->mapVendorRow('acme', ['name' => '  Widget  ']);

        $this->assertEquals('Widget', $result['name']);
    }

    public function test_map_vendor_row_applies_date_format_transform(): void
    {
        VendorSchemaMapping::create([
            'vendor_name' => 'acme',
            'vendor_column' => 'date',
            'erp_column' => 'created_date',
            'transform_rule' => ['type' => 'date_format', 'from' => 'm/d/Y', 'to' => 'Y-m-d'],
        ]);

        $result = $this->service->mapVendorRow('acme', ['date' => '02/15/2026']);

        $this->assertEquals('2026-02-15', $result['created_date']);
    }

    public function test_map_vendor_row_applies_multiply_transform(): void
    {
        VendorSchemaMapping::create([
            'vendor_name' => 'acme',
            'vendor_column' => 'price_cents',
            'erp_column' => 'cost',
            'transform_rule' => ['type' => 'multiply', 'factor' => 0.01],
        ]);

        $result = $this->service->mapVendorRow('acme', ['price_cents' => '1999']);

        $this->assertEqualsWithDelta(19.99, $result['cost'], 0.001);
    }

    public function test_map_vendor_row_returns_original_for_unknown_vendor(): void
    {
        $row = ['col_a' => 'value'];
        $result = $this->service->mapVendorRow('unknown', $row);

        $this->assertEquals($row, $result);
    }

    public function test_map_vendor_row_skips_unmapped_columns(): void
    {
        VendorSchemaMapping::create(['vendor_name' => 'acme', 'vendor_column' => 'sku', 'erp_column' => 'sku']);

        $result = $this->service->mapVendorRow('acme', ['sku' => 'A1', 'extra' => 'ignored']);

        $this->assertEquals(['sku' => 'A1'], $result);
    }

    // -- normalizeAttribute --

    public function test_normalize_attribute_returns_normalized_value(): void
    {
        AttributeNormalization::create([
            'attribute_type' => 'material',
            'raw_value' => 'SS',
            'normalized_value' => 'STAINLESS_STEEL',
        ]);

        $result = $this->service->normalizeAttribute('material', 'SS');

        $this->assertEquals('STAINLESS_STEEL', $result);
    }

    public function test_normalize_attribute_case_insensitive(): void
    {
        AttributeNormalization::create([
            'attribute_type' => 'material',
            'raw_value' => 'SS',
            'normalized_value' => 'STAINLESS_STEEL',
        ]);

        $this->assertEquals('STAINLESS_STEEL', $this->service->normalizeAttribute('material', 'ss'));
        $this->assertEquals('STAINLESS_STEEL', $this->service->normalizeAttribute('material', 'Ss'));
    }

    public function test_normalize_attribute_returns_original_when_no_match(): void
    {
        $result = $this->service->normalizeAttribute('material', 'unknown_material');

        $this->assertEquals('unknown_material', $result);
    }

    // -- validateBrandCompliance --

    public function test_validate_brand_compliance_required_fields(): void
    {
        BrandComplianceRule::create([
            'brand' => 'Brand_1',
            'rule_type' => 'required_field',
            'rule_config' => ['fields' => ['sku', 'name']],
        ]);

        $violations = $this->service->validateBrandCompliance('Brand_1', ['sku' => 'A1']);

        $this->assertCount(1, $violations);
        $this->assertEquals('name', $violations[0]['field']);
    }

    public function test_validate_brand_compliance_naming_convention(): void
    {
        BrandComplianceRule::create([
            'brand' => 'Brand_1',
            'rule_type' => 'naming_convention',
            'rule_config' => ['field' => 'sku', 'pattern' => '/^B1-[A-Z]{2}\d{4}$/'],
        ]);

        $violations = $this->service->validateBrandCompliance('Brand_1', ['sku' => 'INVALID']);

        $this->assertCount(1, $violations);
        $this->assertEquals('naming_convention', $violations[0]['rule_type']);
    }

    public function test_validate_brand_compliance_naming_convention_passes(): void
    {
        BrandComplianceRule::create([
            'brand' => 'Brand_1',
            'rule_type' => 'naming_convention',
            'rule_config' => ['field' => 'sku', 'pattern' => '/^B1-[A-Z]{2}\d{4}$/'],
        ]);

        $violations = $this->service->validateBrandCompliance('Brand_1', ['sku' => 'B1-AB1234']);

        $this->assertEmpty($violations);
    }

    public function test_validate_brand_compliance_price_range(): void
    {
        BrandComplianceRule::create([
            'brand' => 'Brand_1',
            'rule_type' => 'price_range',
            'rule_config' => ['min_msrp' => 10, 'max_msrp' => 5000, 'max_cost_ratio' => 0.7],
        ]);

        $violations = $this->service->validateBrandCompliance('Brand_1', [
            'msrp' => 3,
            'cost' => 2,
        ]);

        $this->assertNotEmpty($violations);
        $this->assertEquals('msrp', $violations[0]['field']);
    }

    public function test_validate_brand_compliance_cost_ratio_violation(): void
    {
        BrandComplianceRule::create([
            'brand' => 'Brand_1',
            'rule_type' => 'price_range',
            'rule_config' => ['max_cost_ratio' => 0.5],
        ]);

        $violations = $this->service->validateBrandCompliance('Brand_1', [
            'msrp' => 100,
            'cost' => 80,
        ]);

        $this->assertCount(1, $violations);
        $this->assertEquals('cost', $violations[0]['field']);
    }

    public function test_validate_brand_compliance_skips_inactive_rules(): void
    {
        BrandComplianceRule::create([
            'brand' => 'Brand_1',
            'rule_type' => 'required_field',
            'rule_config' => ['fields' => ['sku']],
            'is_active' => false,
        ]);

        $violations = $this->service->validateBrandCompliance('Brand_1', []);

        $this->assertEmpty($violations);
    }

    public function test_validate_brand_compliance_no_violations_for_unknown_brand(): void
    {
        $violations = $this->service->validateBrandCompliance('UnknownBrand', []);

        $this->assertEmpty($violations);
    }
}
