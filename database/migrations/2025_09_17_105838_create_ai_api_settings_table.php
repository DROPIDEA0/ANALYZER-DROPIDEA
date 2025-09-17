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
        Schema::create('ai_api_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // openai, anthropic, manus
            $table->string('api_key')->nullable();
            $table->string('api_base_url')->nullable();
            $table->string('model')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable(); // إعدادات إضافية
            $table->timestamps();
            
            $table->unique(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_api_settings');
    }
};
