<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_api_settings', function (Blueprint $table) {
            // إذا كان العمود سابقًا VARCHAR → حوّله إلى TEXT
            $table->text('api_key')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_api_settings', function (Blueprint $table) {
            // عكس العملية (اختياري حسب الحاجة)
            $table->string('api_key', 255)->change();
        });
    }
};

