<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['bank_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_plans');
    }
};