<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->streetName(),
            'description' => fake()->sentence(),
            'venue_name' => fake()->company(),
            'venue_address' => fake()->address(),
            'date' => fake()->dateTimeBetween('+6 months', '+1 year'),
        ];
    }
}
