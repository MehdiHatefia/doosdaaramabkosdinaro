<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->foreignId('bank_plan_id')->nullable()->after('bank_id')->constrained('bank_plans')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('city')->constrained('locations')->nullOnDelete();
        });

        $provinceNames = ['تهران', 'اصفهان', 'یزد', 'خراسان رضوی', 'فارس', 'البرز'];
        $provinces = [];
        foreach ($provinceNames as $name) {
            $slug = Str::slug($name);
            $id = DB::table('locations')->whereNull('parent_id')->where('slug', $slug)->value('id');
            $id ??= DB::table('locations')->insertGetId(['name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
            $provinces[$name] = $id;
        }

        $cities = [
            'تهران' => ['تهران', 'ری', 'شمیرانات'],
            'اصفهان' => ['اصفهان', 'کاشان'],
            'یزد' => ['یزد', 'میبد'],
            'خراسان رضوی' => ['مشهد', 'نیشابور'],
            'فارس' => ['شیراز', 'مرودشت'],
            'البرز' => ['کرج', 'نظرآباد'],
        ];
        $locationIds = [];
        foreach ($cities as $province => $names) {
            foreach ($names as $name) {
                $slug = Str::slug($name);
                $id = DB::table('locations')->where('parent_id', $provinces[$province])->where('slug', $slug)->value('id');
                $id ??= DB::table('locations')->insertGetId(['parent_id' => $provinces[$province], 'name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                $locationIds[$name] = $id;
            }
        }

        foreach (DB::table('advertisements')->whereNotNull('plan')->get() as $advertisement) {
            $planId = DB::table('bank_plans')->where('bank_id', $advertisement->bank_id)->where('title', $advertisement->plan)->value('id');
            $planId ??= DB::table('bank_plans')->insertGetId(['bank_id' => $advertisement->bank_id, 'title' => $advertisement->plan, 'interest_rate' => $advertisement->interest_rate, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $city = explode('،', $advertisement->city)[0];
            $city = [
                'پونک' => 'تهران',
                'سعادت‌آباد' => 'ری',
                'مرکز شهر' => 'اصفهان',
                'سجاد' => 'مشهد',
                'معالی‌آباد' => 'شیراز',
                'عظیمیه' => 'کرج',
            ][$city] ?? $city;
            $locationId = $locationIds[$city] ?? null;
            DB::table('advertisements')->where('id', $advertisement->id)->update(['bank_plan_id' => $planId, 'location_id' => $locationId]);
        }

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropIndex('advertisements_status_type_city_index');
            $table->dropColumn(['plan', 'city']);
            $table->index(['status', 'type', 'location_id']);
            $table->index(['bank_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('plan')->nullable();
            $table->string('city')->nullable();
            $table->dropIndex('advertisements_status_type_location_id_index');
            $table->dropIndex('advertisements_bank_plan_id_status_index');
            $table->dropConstrainedForeignId('bank_plan_id');
            $table->dropConstrainedForeignId('location_id');
            $table->index(['status', 'type', 'city']);
        });
    }
};