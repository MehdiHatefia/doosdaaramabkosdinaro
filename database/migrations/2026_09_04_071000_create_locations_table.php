<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['parent_id', 'slug']);
            $table->index(['parent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};