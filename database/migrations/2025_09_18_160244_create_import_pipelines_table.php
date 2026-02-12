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
        Schema::create('import_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('target_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->time('start_time')->nullable();
            $table->string('frequency', 50);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();

            $table->timestamps();

            $table->index(['name']);
            $table->index(['is_active']);
            $table->index(['frequency']);
            $table->index(['start_time']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_pipelines');
    }
};
