<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('carrier', ['fedex', 'ups', 'usps', 'dhl']);
            $table->string('tracking_number')->unique();
            $table->enum('status', ['packing', 'shipped', 'out_for_delivery', 'delivered', 'exception'])->default('packing');
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
