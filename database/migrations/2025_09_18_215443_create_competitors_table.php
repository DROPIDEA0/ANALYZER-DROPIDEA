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
        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            
            // Reference to main entity
            $table->string('main_place_id')->nullable(); // The business we're comparing against
            $table->string('main_domain')->nullable(); // Or domain if no place_id
            
            // Competitor Info
            $table->string('competitor_place_id')->nullable();
            $table->string('competitor_domain')->nullable();
            $table->string('competitor_name');
            $table->string('competitor_website')->nullable();
            
            // Location & Distance
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->float('distance_km', 8, 2)->nullable(); // distance from main business
            
            // Competitive Metrics
            $table->float('competitor_rating', 3, 2)->nullable();
            $table->integer('competitor_reviews')->default(0);
            $table->integer('competitor_composite_score')->nullable();
            
            // Analysis Comparison
            $table->json('competitive_advantages')->nullable(); // what competitor does better
            $table->json('competitive_gaps')->nullable(); // what main business does better
            $table->float('similarity_score', 3, 2)->nullable(); // how similar they are (0-1)
            
            // Discovery Method
            $table->enum('discovery_method', ['google_nearby', 'manual_add', 'industry_search'])->default('google_nearby');
            $table->boolean('is_direct_competitor')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['main_place_id', 'competitor_place_id']);
            $table->index(['main_domain', 'competitor_domain']);
            $table->index(['distance_km']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitors');
    }
};
