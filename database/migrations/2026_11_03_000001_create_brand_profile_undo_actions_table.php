<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_profile_undo_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('action', 20);
            $table->unsignedBigInteger('profile_id');
            $table->json('snapshot')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'action', 'expires_at']);
            $table->index(['user_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_profile_undo_actions');
    }
};
