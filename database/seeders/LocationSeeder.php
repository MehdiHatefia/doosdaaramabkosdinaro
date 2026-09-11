<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'تهران' => ['تهران', 'ری', 'شمیرانات'],
            'اصفهان' => ['اصفهان', 'کاشان'],
            'یزد' => ['یزد', 'میبد'],
            'خراسان رضوی' => ['مشهد', 'نیشابور'],
            'فارس' => ['شیراز', 'مرودشت'],
            'البرز' => ['کرج', 'نظرآباد'],
        ];

        foreach ($locations as $provinceName => $cityNames) {
            $province = Location::firstOrCreate(['parent_id' => null, 'slug' => Str::slug($provinceName)], ['name' => $provinceName]);
            foreach ($cityNames as $cityName) {
                Location::firstOrCreate(['parent_id' => $province->id, 'slug' => Str::slug($cityName)], ['name' => $cityName]);
            }
        }

        Log::info('Locations seeded', ['function' => __METHOD__, 'user_id' => null, 'payload' => ['province_count' => count($locations)], 'trace' => null]);
    }
}