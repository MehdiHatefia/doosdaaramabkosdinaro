<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->renameColumn('transfer_price', 'assignment_price');
            $table->renameColumn('interest_rate', 'profit_rate');
            $table->unsignedBigInteger('transfer_price')->nullable()->after('assignment_price');
            $table->decimal('interest_rate', 5, 2)->nullable()->after('profit_rate');
            $table->unsignedInteger('installment_count')->default(1)->after('profit_rate');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedBigInteger('views_count')->default(0)->after('rejection_reason');
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE advertisements MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending_approval'");
        }

        DB::table('advertisements')->where('status', 'pending')->update(['status' => 'pending_approval']);
        DB::table('advertisements')->where('status', 'approved')->update(['status' => 'published']);
        DB::table('advertisements')->where('status', 'closed')->update(['status' => 'handed_over']);
    }

    public function down(): void
    {
        DB::table('advertisements')->where('status', 'pending_approval')->update(['status' => 'pending']);
        DB::table('advertisements')->where('status', 'published')->update(['status' => 'approved']);
        DB::table('advertisements')->where('status', 'handed_over')->update(['status' => 'closed']);

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['installment_count', 'rejection_reason', 'views_count']);
            $table->dropColumn(['transfer_price', 'interest_rate']);
            $table->renameColumn('assignment_price', 'transfer_price');
            $table->renameColumn('profit_rate', 'interest_rate');
        });
    }
};
