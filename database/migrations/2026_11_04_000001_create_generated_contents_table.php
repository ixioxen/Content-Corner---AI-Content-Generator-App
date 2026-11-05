<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('custom_tone_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('request_uuid');
            $table->string('content_type', 32);
            $table->text('brief');
            $table->json('brand_profile_snapshot')->nullable();
            $table->json('custom_tone_snapshot')->nullable();
            $table->longText('generated_text')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('failure_message')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'request_uuid']);
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_contents');
    }
};
