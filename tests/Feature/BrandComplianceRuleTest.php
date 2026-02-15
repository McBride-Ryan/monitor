<?php

namespace Tests\Feature;

use App\Models\BrandComplianceRule;
use Database\Seeders\BrandComplianceRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandComplianceRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_table(): void
    {
        $this->assertTrue(\Schema::hasTable('brand_compliance_rules'));
    }

    public function test_factory_creates_rule(): void
    {
        $rule = BrandComplianceRule::factory()->create();

        $this->assertDatabaseHas('brand_compliance_rules', ['id' => $rule->id]);
    }

    public function test_rule_config_cast_to_array(): void
    {
        $rule = BrandComplianceRule::factory()->create([
            'rule_config' => ['field' => 'sku', 'pattern' => '/^TEST/'],
        ]);

        $rule->refresh();
        $this->assertIsArray($rule->rule_config);
        $this->assertEquals('sku', $rule->rule_config['field']);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $rule = BrandComplianceRule::factory()->create(['is_active' => true]);

        $rule->refresh();
        $this->assertTrue($rule->is_active);
    }

    public function test_scope_active(): void
    {
        BrandComplianceRule::factory()->create(['is_active' => true]);
        BrandComplianceRule::factory()->create(['is_active' => false, 'rule_type' => 'other']);

        $this->assertCount(1, BrandComplianceRule::active()->get());
    }

    public function test_scope_for_brand(): void
    {
        BrandComplianceRule::factory()->create(['brand' => 'Brand_1']);
        BrandComplianceRule::factory()->create(['brand' => 'Brand_2', 'rule_type' => 'other']);

        $this->assertCount(1, BrandComplianceRule::forBrand('Brand_1')->get());
    }

    public function test_seeder_creates_rules_for_all_brands(): void
    {
        $this->seed(BrandComplianceRuleSeeder::class);

        foreach (['Brand_1', 'Brand_2', 'Brand_3', 'Brand_4'] as $brand) {
            $this->assertGreaterThan(0, BrandComplianceRule::forBrand($brand)->count());
        }
    }
}
