<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AffiliateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'external_id' => (string) $this->faker->unique()->randomNumber(5),
            'name'        => $this->faker->name(),
            'email'       => $this->faker->unique()->safeEmail(),
            'phone'       => $this->faker->phoneNumber(),
            'city'        => $this->faker->city(),
            'state'       => $this->faker->stateAbbr(),
            'zipcode'     => $this->faker->postcode(),
            'status'      => 'active',
        ];
    }
}