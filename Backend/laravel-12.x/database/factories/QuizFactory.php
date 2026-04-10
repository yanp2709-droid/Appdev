<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word() . ' Quiz',
            'category_id' => Category::factory(),
            'teacher_id' => User::factory()->state(['role' => 'teacher']),
            'difficulty' => $this->faker->randomElement(['Easy', 'Medium', 'Hard']),
            'duration_minutes' => $this->faker->numberBetween(10, 60),
            'timer_enabled' => true,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'max_attempts' => null,
            'allow_review_before_submit' => false,
            'show_score_immediately' => true,
            'show_answers_after_submit' => false,
            'show_correct_answers_after_submit' => false,
        ];
    }

    /**
     * Mark quiz as Easy difficulty
     */
    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'Easy',
        ]);
    }

    /**
     * Mark quiz as Medium difficulty
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'Medium',
        ]);
    }

    /**
     * Mark quiz as Hard difficulty
     */
    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'Hard',
        ]);
    }
}
