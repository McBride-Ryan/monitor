<?php

namespace Database\Seeders;

use App\Models\VendorSchemaMapping;
use Illuminate\Database\Seeder;

class VendorSchemaMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Acme Supply mappings
        VendorSchemaMapping::create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'item_num',
            'erp_column' => 'sku',
        ]);

        VendorSchemaMapping::create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'desc',
            'erp_column' => 'name',
        ]);

        VendorSchemaMapping::create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'mat',
            'erp_column' => 'material',
            'transform_rule' => ['type' => 'uppercase'],
        ]);

        VendorSchemaMapping::create([
            'vendor_name' => 'acme_supply',
            'vendor_column' => 'qty',
            'erp_column' => 'quantity',
            'transform_rule' => ['type' => 'trim'],
        ]);

        // Global Parts mappings
        VendorSchemaMapping::create([
            'vendor_name' => 'global_parts',
            'vendor_column' => 'PartNumber',
            'erp_column' => 'sku',
        ]);

        VendorSchemaMapping::create([
            'vendor_name' => 'global_parts',
            'vendor_column' => 'Description',
            'erp_column' => 'name',
        ]);

        VendorSchemaMapping::create([
            'vendor_name' => 'global_parts',
            'vendor_column' => 'Color',
            'erp_column' => 'color',
        ]);

        VendorSchemaMapping::create([
            'vendor_name' => 'global_parts',
            'vendor_column' => 'UnitPrice',
            'erp_column' => 'cost',
            'transform_rule' => ['type' => 'multiply', 'factor' => 1.0],
        ]);
    }
}
