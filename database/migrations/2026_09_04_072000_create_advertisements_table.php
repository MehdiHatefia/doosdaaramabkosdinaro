<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('plan')->nullable();
            $table->enum('type', ['supply', 'demand']);
            $table->unsignedBigInteger('loan_amount');
            $table->unsignedBigInteger('transfer_price');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->string('city');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'closed'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'type', 'city']);
            $table->index(['bank_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};