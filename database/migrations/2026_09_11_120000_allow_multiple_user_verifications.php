<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_verifications', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropUnique('user_verifications_user_id_unique');
            $table->index(['user_id', 'status', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_verifications', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex('user_verifications_user_id_status_created_at_index');
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};