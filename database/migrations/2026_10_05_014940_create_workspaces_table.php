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
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('accent', 20)->default('violet');
            $table->string('logo_path')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('timezone')->default('UTC');
            $table->string('invoice_prefix', 10)->default('INV');
            $table->decimal('default_tax_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms')->default(14);
            $table->string('plan')->default('starter');
            $table->string('billing_cycle')->default('monthly');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
