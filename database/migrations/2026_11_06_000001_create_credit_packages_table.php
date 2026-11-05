<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('credits_amount');
            $table->decimal('price_amount', 10, 2);
            $table->char('currency', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'credits_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};
