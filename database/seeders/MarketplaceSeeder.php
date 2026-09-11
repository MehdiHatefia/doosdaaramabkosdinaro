<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use App\Models\Bank;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class MarketplaceSeeder extends Seeder
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

        foreach ($banks as $bankData) {
            Bank::firstOrCreate(['slug' => $bankData['slug']], $bankData);
        }

        $advertisements = [
            ['mobile' => '09120000001', 'bank' => 'resalat', 'type' => 'supply', 'plan' => 'طرح مرآت', 'title' => 'امتیاز وام ۳۰۰ میلیونی رسالت', 'loan_amount' => 300, 'transfer_price' => 18, 'city' => 'تهران', 'description' => 'واگذاری امتیاز وام رسالت با شرایط انتقال سریع و مدارک کامل. مناسب برای متقاضیان واجد شرایط طرح مرآت.'],
            ['mobile' => '09120000005', 'bank' => 'melli', 'type' => 'demand', 'plan' => 'طرح اعتبار ملی', 'title' => 'خریدار امتیاز وام فوری', 'loan_amount' => 500, 'transfer_price' => 28, 'city' => 'مشهد', 'description' => 'خریدار امتیاز وام با امکان هماهنگی سریع در مشهد. لطفاً جزئیات طرح و شرایط انتقال را پیش از تماس بررسی کنید.'],
            ['mobile' => '09120000004', 'bank' => 'mehr-iran', 'type' => 'supply', 'plan' => 'وام مهربانی', 'title' => 'واگذاری امتیاز وام مهربانی', 'loan_amount' => 200, 'transfer_price' => 12, 'city' => 'اصفهان', 'description' => 'واگذاری امتیاز وام مهربانی مهر ایران با کارمزد صفر درصد و امکان هماهنگی برای انتقال در اصفهان.'],
            ['mobile' => '09120000004', 'bank' => 'keshavarzi', 'type' => 'supply', 'plan' => 'طرح نوآور', 'title' => 'امتیاز وام ۷۰۰ میلیونی', 'loan_amount' => 700, 'transfer_price' => 42, 'city' => 'شیراز', 'description' => 'امتیاز وام طرح نوآور بانک کشاورزی، مناسب برای مبلغ‌های بالا و متقاضیان دارای شرایط دریافت تسهیلات.'],
            ['mobile' => '09120000005', 'bank' => 'saderat', 'type' => 'demand', 'plan' => 'طرح سپاس', 'title' => 'تقاضای امتیاز وام کم‌کارمزد', 'loan_amount' => 400, 'transfer_price' => 20, 'city' => 'کرج', 'description' => 'تقاضای امتیاز وام کم‌کارمزد در کرج. برای بررسی شرایط و زمان‌بندی انتقال پیام بفرستید.'],
            ['mobile' => '09120000001', 'bank' => 'resalat', 'type' => 'supply', 'plan' => 'طرح مرآت', 'title' => 'امتیاز وام رسالت با انتقال سریع', 'loan_amount' => 900, 'transfer_price' => 55, 'city' => 'ری', 'description' => 'واگذاری امتیاز وام رسالت با مبلغ بالا و انتقال سریع در ری تهران.'],
        ];

        foreach ($advertisements as $advertisementData) {
            $user = User::where('mobile', $advertisementData['mobile'])->firstOrFail();
            $bank = Bank::where('slug', $advertisementData['bank'])->firstOrFail();
            $plan = BankPlan::where('bank_id', $bank->id)->where('title', $advertisementData['plan'])->firstOrFail();
            $location = Location::where('name', $advertisementData['city'])->whereHas('parent')->firstOrFail();

            $advertisement = Advertisement::firstOrCreate(
                ['user_id' => $user->id, 'bank_id' => $bank->id, 'title' => $advertisementData['title']],
                [
                    'bank_plan_id' => $plan->id,
                    'location_id' => $location->id,
                    'type' => $advertisementData['type'],
                    'loan_amount' => $advertisementData['loan_amount'],
                    'transfer_price' => $advertisementData['transfer_price'],
                    'interest_rate' => $plan->interest_rate,
                    'description' => $advertisementData['description'],
                    'status' => 'approved',
                ],
            );

            Log::info('Marketplace sample advertisement seeded', [
                'function' => __METHOD__,
                'user_id' => $user->id,
                'payload' => ['advertisement_id' => $advertisement->id, 'bank_id' => $bank->id],
                'trace' => null,
            ]);
        }
    }
}