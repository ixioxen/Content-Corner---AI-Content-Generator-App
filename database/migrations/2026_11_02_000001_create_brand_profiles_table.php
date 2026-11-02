<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name', 100);
            $table->string('industry', 100)->nullable();
            $table->text('target_audience')->nullable();
            $table->text('personality')->nullable();
            $table->text('writing_style')->nullable();
            $table->json('preferred_words')->nullable();
            $table->json('avoid_words')->nullable();
            $table->string('emoji_usage', 20)->default('moderate');
            $table->string('language', 100)->default('English');
            $table->string('main_platform', 100)->nullable();
            $table->text('pain_points')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_profiles');
    }
};
