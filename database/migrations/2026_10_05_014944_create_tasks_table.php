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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('number')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('estimate_minutes')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status']);
            $table->index(['project_id', 'status', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
