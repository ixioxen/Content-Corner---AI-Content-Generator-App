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
        Schema::create('script_generates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_tone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->json('brand_profile_snapshot')->nullable();
            $table->json('custom_tone_snapshot')->nullable();
            $table->text('generate_script_text')->nullable();
            $table->text('failure_message')->nullable();
            $table->text('brief');
            $table->uuid('request_uuid');
            $table->string('script_type', 32);
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['user_id', 'request_uuid']);
            $table->index(['user_id','status','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_generates');
    }
};
