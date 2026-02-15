<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_compliance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('brand')->index();
            $table->string('rule_type');
            $table->json('rule_config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_compliance_rules');
    }
};
