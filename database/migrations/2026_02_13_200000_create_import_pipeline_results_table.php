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
        Schema::create('import_pipeline_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_uuid')->index();
            $table->foreignId('pipeline_id')->constrained('import_pipelines')->cascadeOnDelete();
            $table->foreignId('execution_id')->constrained('import_pipeline_executions')->cascadeOnDelete();
            $table->json('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_pipeline_results');
    }
};
