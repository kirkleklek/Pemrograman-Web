<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'MK' . fake()->unique()->numerify('#####'),

            'name' => fake()->randomElement([
                'Pemrograman Web',
                'Basis Data',
                'Rekayasa Perangkat Lunak',
                'Jaringan Komputer',
                'Analisis dan Perancangan Sistem',
            ]),

            'description' => fake()->sentence(12),

            'sks' => fake()->numberBetween(2, 4),

            'lecturer_id' => User::factory()->state([
                'role' => 'dosen',
            ]),

            'status' => 'active',
        ];
    }
}