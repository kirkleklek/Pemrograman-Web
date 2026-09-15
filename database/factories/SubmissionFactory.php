<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),

            'user_id' => User::factory()->state([
                'role' => 'mahasiswa',
            ]),

            'file_path' => 'submissions/' . fake()->uuid() . '.pdf',

            'original_name' => fake()->word() . '.pdf',

            'file_size' => fake()->numberBetween(
                50_000,
                5_000_000
            ),

            'submitted_at' => now()->subDays(
                fake()->numberBetween(0, 7)
            ),

            'is_late' => fake()->boolean(15),
        ];
    }
}