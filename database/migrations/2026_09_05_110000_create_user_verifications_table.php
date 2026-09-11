<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('home_phone', 11);
            $table->text('postal_address');
            $table->string('residence_document_path');
            $table->string('national_code', 10);
            $table->string('national_card_serial', 80);
            $table->string('national_card_front_path');
            $table->string('national_card_back_path');
            $table->string('birth_certificate_p1_path');
            $table->string('birth_certificate_p2_path');
            $table->string('job_document_path');
            $table->string('iban', 26);
            $table->boolean('ownership_confirmed')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_verifications');
    }
};
