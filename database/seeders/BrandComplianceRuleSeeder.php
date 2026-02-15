<?php

namespace Database\Seeders;

use App\Models\BrandComplianceRule;
use Illuminate\Database\Seeder;

class BrandComplianceRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Brand_1: strict SKU pattern + required attributes
            ['brand' => 'Brand_1', 'rule_type' => 'naming_convention', 'rule_config' => ['field' => 'sku', 'pattern' => '/^B1-[A-Z]{2}\d{4}$/']],
            ['brand' => 'Brand_1', 'rule_type' => 'required_field', 'rule_config' => ['fields' => ['sku', 'name', 'cost', 'msrp']]],
            ['brand' => 'Brand_1', 'rule_type' => 'price_range', 'rule_config' => ['min_msrp' => 10, 'max_msrp' => 5000, 'max_cost_ratio' => 0.7]],

            // Brand_2: image dimensions + naming
            ['brand' => 'Brand_2', 'rule_type' => 'naming_convention', 'rule_config' => ['field' => 'sku', 'pattern' => '/^B2-\d{6}$/']],
            ['brand' => 'Brand_2', 'rule_type' => 'required_field', 'rule_config' => ['fields' => ['sku', 'name', 'category', 'cost']]],
            ['brand' => 'Brand_2', 'rule_type' => 'image_dimensions', 'rule_config' => ['min_width' => 800, 'min_height' => 800, 'max_size_kb' => 2048]],

            // Brand_3: price ratio + required attrs
            ['brand' => 'Brand_3', 'rule_type' => 'naming_convention', 'rule_config' => ['field' => 'sku', 'pattern' => '/^B3-[A-Z]{3}-\d{3}$/']],
            ['brand' => 'Brand_3', 'rule_type' => 'price_range', 'rule_config' => ['min_msrp' => 5, 'max_msrp' => 10000, 'max_cost_ratio' => 0.65]],
            ['brand' => 'Brand_3', 'rule_type' => 'required_field', 'rule_config' => ['fields' => ['sku', 'name', 'msrp', 'retail_price']]],

            // Brand_4: loose requirements
            ['brand' => 'Brand_4', 'rule_type' => 'naming_convention', 'rule_config' => ['field' => 'sku', 'pattern' => '/^B4-\d{4,8}$/']],
            ['brand' => 'Brand_4', 'rule_type' => 'required_field', 'rule_config' => ['fields' => ['sku', 'name']]],
        ];

        foreach ($rules as $rule) {
            BrandComplianceRule::updateOrCreate(
                ['brand' => $rule['brand'], 'rule_type' => $rule['rule_type']],
                ['rule_config' => $rule['rule_config'], 'is_active' => true]
            );
        }
    }
}
