<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_text' => $this->faker->sentence(),
            'category_id' => Category::factory(),
            'question_type' => $this->faker->randomElement(['mcq', 'tf', 'multi_select', 'ordering', 'short_answer']),
            'points' => $this->faker->numberBetween(1, 10),
            'answer_key' => $this->faker->word(),
        ];
    }
}
