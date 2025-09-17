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
        Schema::create('website_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('region');
            $table->enum('analysis_type', ['full', 'seo', 'performance', 'competitors']);
            $table->json('analysis_data');
            $table->integer('seo_score')->nullable();
            $table->integer('performance_score')->nullable();
            $table->decimal('load_time', 5, 2)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_analyses');
    }
};
