<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('name');
            $table->string('national_code', 10)->nullable()->unique()->after('nickname');
            $table->date('birth_date')->nullable()->after('national_code');
            $table->string('iban', 26)->nullable()->after('birth_date');
            $table->string('avatar', 255)->nullable()->after('iban');
            $table->foreignId('province_id')->nullable()->after('avatar')->constrained('locations')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('province_id')->constrained('locations')->nullOnDelete();
            $table->boolean('show_phone_publicly')->default(true)->after('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropUnique(['national_code']);
            $table->dropColumn([
                'nickname', 'national_code', 'birth_date', 'iban', 'avatar',
                'province_id', 'city_id', 'show_phone_publicly',
            ]);
        });
    }
};
