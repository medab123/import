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
        Schema::create('import_pipeline_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('import_pipelines')->onDelete('cascade');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0.00);
            $table->decimal('processing_time', 10, 3)->default(0.000);
            $table->integer('memory_usage')->default(0);
            $table->text('error_message')->nullable();
            $table->json('result_data')->nullable();
            $table->timestamps();

            $table->index(['pipeline_id']);
            $table->index(['status']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_pipeline_executions');
    }
};
