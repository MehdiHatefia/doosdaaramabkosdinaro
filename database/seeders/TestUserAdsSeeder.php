<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use App\Models\Bank;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestUserAdsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(8);
        if (! $user) {
            Log::warning('Test user ads seeder skipped: user 8 does not exist', [
                'function' => __METHOD__, 'user_id' => 8, 'payload' => [], 'trace' => null,
            ]);
            $this->command?->warn('کاربر با شناسه ۸ پیدا نشد؛ Seeder اجرا نشد.');
            return;
        }

        $banks = Bank::query()->get();
        $plans = BankPlan::query()->get();
        $cities = Location::query()->whereNotNull('parent_id')->get();

        if ($banks->isEmpty() || $plans->isEmpty() || $cities->isEmpty()) {
            Log::warning('Test user ads seeder skipped: required foreign keys are missing', [
                'function' => __METHOD__, 'user_id' => $user->id,
                'payload' => ['banks' => $banks->count(), 'plans' => $plans->count(), 'cities' => $cities->count()],
                'trace' => null,
            ]);
            $this->command?->warn('برای ساخت آگهی تستی بانک، طرح بانکی و شهر لازم است.');
            return;
        }

        $statuses = array_merge(
            array_fill(0, 10, Advertisement::STATUS_PUBLISHED),
            array_fill(0, 8, Advertisement::STATUS_PENDING_APPROVAL),
            array_fill(0, 6, Advertisement::STATUS_REJECTED),
            array_fill(0, 4, Advertisement::STATUS_HANDED_OVER),
            array_fill(0, 2, Advertisement::STATUS_EXPIRED),
        );
        $loanAmounts = [50000000, 85000000, 120000000, 180000000, 250000000, 320000000, 450000000, 600000000, 750000000, 900000000, 1100000000, 1350000000, 1600000000, 2000000000];
        $assignmentPrices = [5000000, 9000000, 15000000, 22000000, 35000000, 48000000, 65000000, 85000000, 110000000, 145000000, 190000000, 230000000, 300000000];
        $profitRates = [4, 18, 21, 23];
        $installments = [12, 24, 36, 48, 60];
        $rejectionReasons = [
            'مبلغ واگذاری با شرایط بانک همخوانی ندارد.',
            'عنوان آگهی کوتاه است و نیاز به اصلاح دارد.',
            'مدارک و اطلاعات مالی آگهی کامل نیست.',
            'درصد کارمزد واردشده با طرح انتخابی تطابق ندارد.',
            'اطلاعات شهر محل ارائه تسهیلات نیاز به بررسی دارد.',
            'توضیحات آگهی برای انتشار عمومی کافی نیست.',
        ];
        $titleParts = [
            'واگذاری امتیاز وام فوری با انتقال سریع',
            'تقاضای خرید امتیاز تسهیلات کم‌کارمزد',
            'امتیاز وام مناسب خانواده‌ها با شرایط منعطف',
            'واگذاری امتیاز تسهیلات برای خرید مسکن و تعمیرات',
            'خریدار امتیاز وام با امکان هماهنگی و بررسی مدارک',
            'امتیاز وام ویژه با مبلغ بالا و بازپرداخت بلندمدت',
        ];
        $descriptionParts = [
            'این آگهی برای تست نمایش کارت، صفحه جزئیات و فیلترهای پنل کاربری ایجاد شده است. شرایط انتقال پس از بررسی مدارک و هماهنگی با متقاضی اعلام می‌شود.',
            'اطلاعات این مورد شامل مبلغ تسهیلات، هزینه واگذاری و تعداد اقساط است. لطفاً پیش از هرگونه هماهنگی، شرایط طرح بانکی و شهر انتخابی را بررسی کنید.',
            "توضیحات چندخطی تستی برای بررسی ارتفاع کارت و شکست متن در نمایشگرهای مختلف.\nهماهنگی انتقال پس از تایید اطلاعات انجام خواهد شد.",
        ];

        DB::transaction(function () use ($user, $banks, $plans, $cities, $statuses, $loanAmounts, $assignmentPrices, $profitRates, $installments, $rejectionReasons, $titleParts, $descriptionParts): void {
            Advertisement::withTrashed()
                ->where('user_id', $user->id)
                ->where('title', 'like', '[تست کاربر ۸]%')
                ->forceDelete();

            foreach ($statuses as $index => $status) {
                $bank = $banks[$index % $banks->count()];
                $plan = $plans->firstWhere('bank_id', $bank->id) ?: $plans[$index % $plans->count()];
                $city = $cities[$index % $cities->count()];
                $title = sprintf('[تست کاربر ۸] %s شماره %02d', $titleParts[$index % count($titleParts)], $index + 1);
                $createdAt = now()->subDays(($index * 2) % 61)->subHours($index % 7);

                Advertisement::create([
                    'user_id' => $user->id,
                    'bank_id' => $bank->id,
                    'bank_plan_id' => $plan->id,
                    'location_id' => $city->id,
                    'type' => $index % 3 === 0 ? 'demand' : 'supply',
                    'title' => $title,
                    'loan_amount' => $loanAmounts[$index % count($loanAmounts)],
                    'assignment_price' => $assignmentPrices[$index % count($assignmentPrices)],
                    'profit_rate' => $profitRates[$index % count($profitRates)],
                    'installment_count' => $installments[$index % count($installments)],
                    'description' => $descriptionParts[$index % count($descriptionParts)],
                    'status' => $status,
                    'rejection_reason' => $status === Advertisement::STATUS_REJECTED ? $rejectionReasons[$index % count($rejectionReasons)] : null,
                    'views_count' => ($index * 17) % 500,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        });

        Log::info('Test user ads seeded', [
            'function' => __METHOD__, 'user_id' => $user->id,
            'payload' => ['count' => count($statuses), 'status_counts' => array_count_values($statuses)], 'trace' => null,
        ]);
        $this->command?->info('۳۰ آگهی تستی برای کاربر ۸ ساخته شد.');
    }
}
