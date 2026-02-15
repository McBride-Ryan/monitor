<?php

namespace Tests\Feature;

use App\Models\AttributeNormalization;
use Database\Seeders\AttributeNormalizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_table(): void
    {
        $this->assertTrue(\Schema::hasTable('attribute_normalizations'));
    }

    public function test_seeder_creates_normalizations(): void
    {
        $this->seed(AttributeNormalizationSeeder::class);

        $this->assertGreaterThanOrEqual(30, AttributeNormalization::count());
    }

    public function test_seeder_produces_expected_material_normalizations(): void
    {
        $this->seed(AttributeNormalizationSeeder::class);

        $this->assertDatabaseHas('attribute_normalizations', [
            'attribute_type' => 'material',
            'raw_value' => 'SS',
            'normalized_value' => 'STAINLESS_STEEL',
        ]);

        $this->assertDatabaseHas('attribute_normalizations', [
            'attribute_type' => 'material',
            'raw_value' => 'SST',
            'normalized_value' => 'STAINLESS_STEEL',
        ]);
    }

    public function test_scope_for_type(): void
    {
        $this->seed(AttributeNormalizationSeeder::class);

        $materials = AttributeNormalization::forType('material')->get();
        $colors = AttributeNormalization::forType('color')->get();

        $this->assertGreaterThan(0, $materials->count());
        $this->assertGreaterThan(0, $colors->count());
        $this->assertTrue($materials->every(fn ($n) => $n->attribute_type === 'material'));
    }

    public function test_unique_constraint_on_type_and_raw_value(): void
    {
        AttributeNormalization::create([
            'attribute_type' => 'material',
            'raw_value' => 'TEST',
            'normalized_value' => 'TEST_VALUE',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AttributeNormalization::create([
            'attribute_type' => 'material',
            'raw_value' => 'TEST',
            'normalized_value' => 'OTHER_VALUE',
        ]);
    }
}
