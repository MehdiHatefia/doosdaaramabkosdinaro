<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\BankPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BankPlan> */
class BankPlanFactory extends Factory
{
    protected $model = BankPlan::class;

    public function definition(): array
    {
        return [
            'bank_id' => Bank::factory(),
            'title' => fake()->unique()->sentence(2),
            'interest_rate' => fake()->randomFloat(2, 0, 20),
            'is_active' => true,
        ];
    }
}