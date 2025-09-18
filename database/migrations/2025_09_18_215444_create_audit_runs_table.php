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
        Schema::create('audit_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_analysis_id')->constrained('website_analyses_advanced')->onDelete('cascade');
            
            // Job Details
            $table->string('audit_type'); // 'pagespeed', 'wappalyzer', 'axe', 'security', 'seo'
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'timeout'])->default('pending');
            $table->string('job_id')->nullable(); // Laravel Queue job ID
            
            // Execution Tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_seconds')->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            
            // Results & Errors
            $table->json('result_data')->nullable(); // the actual results
            $table->json('error_details')->nullable(); // error logs if failed
            $table->text('error_message')->nullable();
            $table->json('debug_info')->nullable(); // additional debug data
            
            // Resource Usage
            $table->integer('memory_usage_mb')->nullable();
            $table->float('cpu_usage_seconds', 8, 2)->nullable();
            
            // External API calls tracking
            $table->integer('api_calls_made')->default(0);
            $table->json('api_response_times')->nullable(); // track API performance
            
            $table->timestamps();
            
            // Indexes
            $table->index(['website_analysis_id', 'audit_type']);
            $table->index(['status']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_runs');
    }
};
