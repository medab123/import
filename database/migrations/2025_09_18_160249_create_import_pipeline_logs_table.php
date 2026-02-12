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
        Schema::create('import_pipeline_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('import_pipeline_executions')->onDelete('cascade');
            $table->enum('log_level', ['debug', 'info', 'warning', 'error', 'critical']);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['execution_id']);
            $table->index(['log_level']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_pipeline_logs');
    }
};
