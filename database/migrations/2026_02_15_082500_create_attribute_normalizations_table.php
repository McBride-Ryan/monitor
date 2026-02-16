<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_normalizations', function (Blueprint $table) {
            $table->id();
            $table->string('attribute_type');
            $table->string('raw_value')->index();
            $table->string('normalized_value');
            $table->timestamps();

            $table->unique(['attribute_type', 'raw_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_normalizations');
    }
};
