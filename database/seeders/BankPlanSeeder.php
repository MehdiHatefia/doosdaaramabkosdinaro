<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class BankPlanSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['name' => 'بانک ملی', 'slug' => 'melli'],
            ['name' => 'بانک رسالت', 'slug' => 'resalat'],
            ['name' => 'مهر ایران', 'slug' => 'mehr-iran'],
            ['name' => 'بانک کشاورزی', 'slug' => 'keshavarzi'],
            ['name' => 'بانک صادرات', 'slug' => 'saderat'],
        ];
        $plans = [
            'melli' => [['title' => 'طرح اعتبار ملی', 'interest_rate' => 4], ['title' => 'طرح مهربانی ملی', 'interest_rate' => 2]],
            'resalat' => [['title' => 'طرح مرآت', 'interest_rate' => 2], ['title' => 'طرح امتیازی رسالت', 'interest_rate' => 4]],
            'mehr-iran' => [['title' => 'وام مهربانی', 'interest_rate' => 0], ['title' => 'طرح امتیازی مهر', 'interest_rate' => 4]],
            'keshavarzi' => [['title' => 'طرح نوآور', 'interest_rate' => 8], ['title' => 'طرح احسان', 'interest_rate' => 6]],
            'saderat' => [['title' => 'طرح سپاس', 'interest_rate' => 12], ['title' => 'طرح صبا', 'interest_rate' => 10]],
        ];

        foreach ($banks as $bankData) {
            $bank = Bank::firstOrCreate(['slug' => $bankData['slug']], $bankData + ['is_active' => true]);
            if (!$bank->is_active) continue;
            foreach ($plans[$bankData['slug']] as $planData) {
                BankPlan::firstOrCreate(['bank_id' => $bank->id, 'title' => $planData['title']], $planData + ['is_active' => true]);
            }
        }

        Log::info('Bank plans seeded', ['function' => __METHOD__, 'user_id' => null, 'payload' => ['bank_count' => count($banks)], 'trace' => null]);
    }
}