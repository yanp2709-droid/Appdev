<?php

namespace Database\Factories;

use App\Models\Quiz_attempt;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class Quiz_attemptFactory extends Factory
{
    protected $model = Quiz_attempt::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'quiz_id' => Quiz::factory(),
            'score' => $this->faker->numberBetween(0, 100),
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'submitted_at' => null,
            'total_items' => $this->faker->numberBetween(5, 20),
            'answered_count' => $this->faker->numberBetween(0, 20),
            'correct_answers' => $this->faker->numberBetween(0, 20),
            'score_percent' => $this->faker->randomFloat(2, 0, 100),
            'question_sequence' => [],
            'last_activity_at' => now(),
            'last_viewed_question_id' => null,
            'last_viewed_question_index' => 0,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subMinutes(5),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'submitted_at' => null,
        ]);
    }
}
