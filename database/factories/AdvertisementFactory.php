<?php

namespace Database\Factories;

use App\Models\Advertisement;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Advertisement> */
class AdvertisementFactory extends Factory
{
    protected $model = Advertisement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank_plan_id' => BankPlan::factory(),
            'bank_id' => fn (array $attributes) => BankPlan::find($attributes['bank_plan_id'])->bank_id,
            'location_id' => Location::factory(),
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(['supply', 'demand']),
            'loan_amount' => fake()->numberBetween(100, 1000),
            'transfer_price' => fake()->numberBetween(5, 100),
            'assignment_price' => fn (array $attributes) => $attributes['transfer_price'],
            'interest_rate' => fake()->randomFloat(2, 0, 20),
            'profit_rate' => fn (array $attributes) => $attributes['interest_rate'],
            'installment_count' => fake()->numberBetween(12, 60),
            'description' => fake()->paragraph(),
            'status' => 'published',
        ];
    }
}