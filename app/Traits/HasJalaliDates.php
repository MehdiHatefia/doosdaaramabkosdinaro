<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeInterface;

trait HasJalaliDates
{
    public function setAttribute($key, $value)
    {
        if ($this->usesJalaliDateField($key) && is_string($value)) {
            $value = $this->normalizeJalaliInput($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        foreach ($this->jalaliDateFields() as $field) {
            if (! array_key_exists($field, $attributes) || blank($attributes[$field])) {
                continue;
            }

            try {
                $attributes[$field] = self::gregorianToJalali($attributes[$field]);
            } catch (\Throwable) {
                // Keep malformed legacy values unchanged during serialization.
            }
        }

        return $attributes;
    }

    public static function jalaliToGregorian(string $value): string
    {
        $parts = preg_split('/[-\/]/', self::normalizeDigits(trim($value)));
        [$jy, $jm, $jd] = array_map('intval', $parts);
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd;
        $days += $jm < 7 ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $monthDays = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 1;
        while ($gm <= 12 && $gd > $monthDays[$gm]) {
            $gd -= $monthDays[$gm++];
        }

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    public static function gregorianToJalali($value): string
    {
        $date = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);
        $gy = (int) $date->format('Y');
        $gm = (int) $date->format('m');
        $gd = (int) $date->format('d');
        $gDayNo = (365 * ($gy - 1600)) + intdiv($gy - 1600 + 3, 4) - intdiv($gy - 1600 + 99, 100) + intdiv($gy - 1600 + 399, 400) - 1;
        $gMonthDays = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($i = 1; $i < $gm; $i++) $gDayNo += $gMonthDays[$i];
        $gDayNo += $gd;
        $jDayNo = $gDayNo - 79;
        $jNp = intdiv($jDayNo, 12053);
        $jDayNo %= 12053;
        $jy = 979 + (33 * $jNp) + (4 * intdiv($jDayNo, 1461));
        $jDayNo %= 1461;
        if ($jDayNo >= 366) {
            $jy += intdiv($jDayNo - 1, 365);
            $jDayNo = ($jDayNo - 1) % 365;
        }
        $jm = $jDayNo < 186 ? 1 + intdiv($jDayNo, 31) : 7 + intdiv($jDayNo - 186, 30);
        $jd = 1 + ($jDayNo < 186 ? $jDayNo % 31 : ($jDayNo - 186) % 30);

        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }

    protected function jalaliDateFields(): array
    {
        return property_exists($this, 'jalaliDateFields') ? $this->jalaliDateFields : [];
    }

    private function usesJalaliDateField(string $key): bool
    {
        return in_array($key, $this->jalaliDateFields(), true);
    }

    private function normalizeJalaliInput(string $value): string
    {
        $value = self::normalizeDigits($value);
        if (preg_match('/^(13|14)\d{2}[-\/]\d{1,2}[-\/]\d{1,2}/', $value)) {
            $date = substr($value, 0, 10);
            $time = strlen($value) > 10 ? substr($value, 10) : '';
            return self::jalaliToGregorian($date) . $time;
        }

        return $value;
    }

    private static function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
