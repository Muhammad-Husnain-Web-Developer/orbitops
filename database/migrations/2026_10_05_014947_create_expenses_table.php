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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('vendor')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('spent_on');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('billable')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'spent_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
