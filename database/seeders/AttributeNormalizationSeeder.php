<?php

namespace Database\Seeders;

use App\Models\AttributeNormalization;
use Illuminate\Database\Seeder;

class AttributeNormalizationSeeder extends Seeder
{
    public function run(): void
    {
        $normalizations = [
            // Materials
            ['attribute_type' => 'material', 'raw_value' => 'SS', 'normalized_value' => 'STAINLESS_STEEL'],
            ['attribute_type' => 'material', 'raw_value' => 'SST', 'normalized_value' => 'STAINLESS_STEEL'],
            ['attribute_type' => 'material', 'raw_value' => 'Stainless Steel', 'normalized_value' => 'STAINLESS_STEEL'],
            ['attribute_type' => 'material', 'raw_value' => 'stainless', 'normalized_value' => 'STAINLESS_STEEL'],
            ['attribute_type' => 'material', 'raw_value' => 'AL', 'normalized_value' => 'ALUMINUM'],
            ['attribute_type' => 'material', 'raw_value' => 'Aluminium', 'normalized_value' => 'ALUMINUM'],
            ['attribute_type' => 'material', 'raw_value' => 'Aluminum', 'normalized_value' => 'ALUMINUM'],
            ['attribute_type' => 'material', 'raw_value' => 'BRS', 'normalized_value' => 'BRASS'],
            ['attribute_type' => 'material', 'raw_value' => 'Brass', 'normalized_value' => 'BRASS'],
            ['attribute_type' => 'material', 'raw_value' => 'CS', 'normalized_value' => 'CARBON_STEEL'],
            ['attribute_type' => 'material', 'raw_value' => 'Carbon Steel', 'normalized_value' => 'CARBON_STEEL'],
            ['attribute_type' => 'material', 'raw_value' => 'PVC', 'normalized_value' => 'PVC'],
            ['attribute_type' => 'material', 'raw_value' => 'Polyvinyl Chloride', 'normalized_value' => 'PVC'],

            // Colors
            ['attribute_type' => 'color', 'raw_value' => 'BLK', 'normalized_value' => 'BLACK'],
            ['attribute_type' => 'color', 'raw_value' => 'Blk', 'normalized_value' => 'BLACK'],
            ['attribute_type' => 'color', 'raw_value' => 'Black', 'normalized_value' => 'BLACK'],
            ['attribute_type' => 'color', 'raw_value' => 'WHT', 'normalized_value' => 'WHITE'],
            ['attribute_type' => 'color', 'raw_value' => 'White', 'normalized_value' => 'WHITE'],
            ['attribute_type' => 'color', 'raw_value' => 'SLV', 'normalized_value' => 'SILVER'],
            ['attribute_type' => 'color', 'raw_value' => 'Silver', 'normalized_value' => 'SILVER'],
            ['attribute_type' => 'color', 'raw_value' => 'CHR', 'normalized_value' => 'CHROME'],
            ['attribute_type' => 'color', 'raw_value' => 'Chrome', 'normalized_value' => 'CHROME'],
            ['attribute_type' => 'color', 'raw_value' => 'BRZ', 'normalized_value' => 'BRONZE'],
            ['attribute_type' => 'color', 'raw_value' => 'Bronze', 'normalized_value' => 'BRONZE'],

            // Units
            ['attribute_type' => 'unit', 'raw_value' => 'ea', 'normalized_value' => 'EACH'],
            ['attribute_type' => 'unit', 'raw_value' => 'EA', 'normalized_value' => 'EACH'],
            ['attribute_type' => 'unit', 'raw_value' => 'Each', 'normalized_value' => 'EACH'],
            ['attribute_type' => 'unit', 'raw_value' => 'pc', 'normalized_value' => 'PIECE'],
            ['attribute_type' => 'unit', 'raw_value' => 'pcs', 'normalized_value' => 'PIECE'],
            ['attribute_type' => 'unit', 'raw_value' => 'Piece', 'normalized_value' => 'PIECE'],
            ['attribute_type' => 'unit', 'raw_value' => 'bx', 'normalized_value' => 'BOX'],
            ['attribute_type' => 'unit', 'raw_value' => 'Box', 'normalized_value' => 'BOX'],
            ['attribute_type' => 'unit', 'raw_value' => 'cs', 'normalized_value' => 'CASE'],
            ['attribute_type' => 'unit', 'raw_value' => 'Case', 'normalized_value' => 'CASE'],
            ['attribute_type' => 'unit', 'raw_value' => 'ft', 'normalized_value' => 'FOOT'],
            ['attribute_type' => 'unit', 'raw_value' => 'Foot', 'normalized_value' => 'FOOT'],
            ['attribute_type' => 'unit', 'raw_value' => 'Feet', 'normalized_value' => 'FOOT'],
        ];

        foreach ($normalizations as $norm) {
            AttributeNormalization::updateOrCreate(
                ['attribute_type' => $norm['attribute_type'], 'raw_value' => $norm['raw_value']],
                ['normalized_value' => $norm['normalized_value']]
            );
        }
    }
}
