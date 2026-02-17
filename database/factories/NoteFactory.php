<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
        ];
    }

    /**
     * Indicate that the note is pinned.
     */
    public function pinned(int $order = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'pin_order' => $order,
        ]);
    }
}
