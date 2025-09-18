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
        Schema::create('website_analyses_advanced', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Basic info
            $table->string('url');
            $table->string('domain');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Core Web Vitals & Performance
            $table->json('core_web_vitals')->nullable(); // LCP, FID, CLS
            $table->integer('pagespeed_mobile')->nullable();
            $table->integer('pagespeed_desktop')->nullable();
            $table->integer('lighthouse_performance')->nullable();
            $table->integer('lighthouse_seo')->nullable();
            $table->integer('lighthouse_accessibility')->nullable();
            $table->integer('lighthouse_best_practices')->nullable();
            
            // Network Performance
            $table->float('page_size_mb', 8, 2)->nullable();
            $table->integer('total_requests')->nullable();
            $table->json('compression_details')->nullable(); // GZIP/Brotli info
            $table->string('http_version')->nullable(); // HTTP/1.1, HTTP/2, HTTP/3
            
            // Technology Stack
            $table->json('stack_detection')->nullable(); // من Wappalyzer
            $table->json('technologies')->nullable(); // Frontend, Backend, Analytics, etc.
            
            // Security Analysis
            $table->json('security_headers')->nullable(); // HSTS, CSP, X-Frame-Options, etc.
            $table->boolean('has_ssl')->default(false);
            $table->string('ssl_grade')->nullable(); // A+, A, B, etc.
            $table->json('security_issues')->nullable();
            
            // Accessibility
            $table->json('accessibility_results')->nullable(); // من axe-core
            $table->integer('accessibility_score')->nullable();
            $table->json('accessibility_violations')->nullable();
            
            // Metadata & Schema
            $table->json('metadata')->nullable(); // title, description, H1-H3
            $table->json('open_graph')->nullable();
            $table->json('twitter_cards')->nullable();
            $table->json('schema_org')->nullable();
            
            // SEO & Indexing
            $table->boolean('has_robots_txt')->default(false);
            $table->boolean('has_sitemap')->default(false);
            $table->json('canonical_issues')->nullable();
            $table->json('indexing_status')->nullable();
            
            // Composite Scoring
            $table->integer('composite_score')->nullable(); // Overall score
            $table->integer('seo_score')->nullable(); // 30%
            $table->integer('performance_score')->nullable(); // 25%
            $table->integer('security_score')->nullable(); // 15%
            $table->integer('ux_score')->nullable(); // 15%
            $table->integer('maps_presence_score')->nullable(); // 15%
            
            // Analysis tracking
            $table->timestamp('analysis_started_at')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();
            $table->integer('total_analysis_time')->nullable(); // in seconds
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'domain']);
            $table->index(['status']);
            $table->index(['composite_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_analyses_advanced');
    }
};
