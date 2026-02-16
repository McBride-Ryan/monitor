<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_schema_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name')->index();
            $table->string('vendor_column');
            $table->string('erp_column');
            $table->json('transform_rule')->nullable();
            $table->timestamps();

            $table->unique(['vendor_name', 'vendor_column']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_schema_mappings');
    }
};
