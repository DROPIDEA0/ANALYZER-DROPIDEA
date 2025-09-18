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
        Schema::create('gmb_entities', function (Blueprint $table) {
            $table->id();
            $table->string('place_id')->unique(); // Google Place ID
            
            // Basic Business Info
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Location Data
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('address_components')->nullable(); // parsed address
            
            // Business Details
            $table->float('rating', 3, 2)->nullable(); // 4.5 out of 5
            $table->integer('total_reviews')->default(0);
            $table->json('rating_distribution')->nullable(); // 5-star breakdown
            $table->json('business_hours')->nullable();
            $table->json('photos')->nullable(); // array of photo URLs/references
            $table->string('price_level')->nullable(); // $, $$, $$$, $$$$
            
            // Categories & Types
            $table->json('categories')->nullable(); // business categories
            $table->json('types')->nullable(); // Google Place types
            
            // Reviews & Sentiment
            $table->json('recent_reviews')->nullable(); // latest reviews
            $table->float('sentiment_score', 3, 2)->nullable(); // -1 to +1
            $table->json('sentiment_breakdown')->nullable(); // positive/neutral/negative
            
            // Additional Data
            $table->boolean('is_verified')->default(false);
            $table->string('status')->default('active'); // active, closed_permanently, etc.
            $table->json('attributes')->nullable(); // wheelchair_accessible, etc.
            
            // Tracking
            $table->timestamp('last_updated_from_google')->nullable();
            $table->integer('update_frequency_days')->default(7); // how often to refresh
            
            $table->timestamps();
            
            // Indexes
            $table->index(['place_id']);
            $table->index(['rating', 'total_reviews']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmb_entities');
    }
};
