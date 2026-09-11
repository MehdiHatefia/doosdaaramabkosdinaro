<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Bank> */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' بانک';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}