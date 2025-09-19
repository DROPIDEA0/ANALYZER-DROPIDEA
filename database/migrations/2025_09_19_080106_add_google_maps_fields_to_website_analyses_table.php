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
        Schema::table('website_analyses', function (Blueprint $table) {
            $table->string('gmb_place_id')->nullable()->after('analysis_data');
            $table->string('gmb_name')->nullable()->after('gmb_place_id');
            $table->text('gmb_address')->nullable()->after('gmb_name');
            $table->decimal('gmb_latitude', 10, 8)->nullable()->after('gmb_address');
            $table->decimal('gmb_longitude', 11, 8)->nullable()->after('gmb_latitude');
            $table->decimal('gmb_rating', 2, 1)->nullable()->after('gmb_longitude');
            $table->integer('gmb_reviews_count')->nullable()->after('gmb_rating');
            $table->json('gmb_data')->nullable()->after('gmb_reviews_count');
            $table->json('competitors_data')->nullable()->after('gmb_data');
            $table->integer('composite_score')->nullable()->after('competitors_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'gmb_place_id',
                'gmb_name', 
                'gmb_address',
                'gmb_latitude',
                'gmb_longitude',
                'gmb_rating',
                'gmb_reviews_count',
                'gmb_data',
                'competitors_data',
                'composite_score'
            ]);
        });
    }
};
