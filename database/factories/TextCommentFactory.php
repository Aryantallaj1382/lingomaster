<?php
namespace Database\Factories;

use App\Models\shop\TextComment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TextCommentFactory extends Factory
{
    protected $model = TextComment::class;

    public function definition()
    {
        return [
            'content' => $this->faker->paragraph,
            'product_id' => \App\Models\shop\Product::factory(),
        ];
    }
}
