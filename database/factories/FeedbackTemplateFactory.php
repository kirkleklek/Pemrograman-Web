<?php

namespace Database\Factories;

use App\Models\FeedbackTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackTemplate>
 */
class FeedbackTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $templates = [
            [
                'title' => 'Jawaban Sudah Baik',
                'content' => 'Jawaban sudah baik dan menunjukkan pemahaman yang tepat terhadap materi.',
            ],
            [
                'title' => 'Perlu Menambahkan Contoh',
                'content' => 'Penjelasan sudah cukup baik, tetapi perlu menambahkan contoh agar konsep lebih mudah dipahami.',
            ],
            [
                'title' => 'Perlu Penjelasan Lebih Rinci',
                'content' => 'Beberapa bagian jawaban perlu dijelaskan lebih rinci agar alur pemikiran mudah diikuti.',
            ],
        ];

        $template = fake()->randomElement($templates);

        return [
            'lecturer_id' => User::factory()->state([
                'role' => 'dosen',
            ]),
            'title' => $template['title'],
            'content' => $template['content'],
        ];
    }
}
