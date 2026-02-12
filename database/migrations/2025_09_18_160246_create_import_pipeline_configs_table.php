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
        Schema::create('import_pipeline_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('import_pipelines')->onDelete('cascade');
            $table->string('type');
            $table->json('config_data');
            $table->timestamps();

            $table->index(['pipeline_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_pipeline_configs');
    }
};
