<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => 'Ticket - ' . fake()->word(),
            'price' => fake()->randomNumber(4),
            'available_quantity' => fake()->randomNumber(2),
        ];
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn(array $attributes) => [
            'event_id' => $event->id,
        ]);
    }
}
