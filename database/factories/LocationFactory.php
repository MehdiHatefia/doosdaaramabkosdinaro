<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => fake()->unique()->city(),
            'slug' => fake()->unique()->slug(),
        ];
    }
}