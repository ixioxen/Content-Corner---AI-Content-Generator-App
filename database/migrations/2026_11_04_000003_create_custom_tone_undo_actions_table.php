<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_tone_undo_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->unsignedBigInteger('custom_tone_id');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'custom_tone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_tone_undo_actions');
    }
};
