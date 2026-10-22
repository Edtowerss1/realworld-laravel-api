<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $title = $this->faker->unique()->sentence(4);

        return [
            'user_id' => User::factory(), // Crea un usuario automáticamente si no se le pasa uno
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph(1),
            'body' => $this->faker->paragraphs(3, true),
        ];
    }
}
