<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->index();
            $table->string('code');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['mobile', 'consumed_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};