<?php

namespace Database\Factories;

use App\Models\Affiliate;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'external_id'  => $this->faker->unique()->randomNumber(5),
            'affiliate_id' => Affiliate::factory(),
            'status'       => 'pending',
            'total'        => $this->faker->randomFloat(2, 10, 5000),
        ];
    }
}