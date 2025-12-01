<?php
namespace Database\Factories;

use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'author' => $this->faker->name,
            'Volume_number' => $this->faker->randomDigit,
            'page_number' => $this->faker->numberBetween(100, 500),
            'discount_percentage' => $this->faker->numberBetween(100, 500),
            'year_of_publication' => $this->faker->year,
            'price' => $this->faker->randomFloat(2, 10, 100),
            'off_price' => $this->faker->randomFloat(2, 5, 50),
            'weight' => $this->faker->numberBetween(200, 500),
            'shabak_number' => $this->faker->unique()->numerify('###########'),
            'fipa_number' => $this->faker->unique()->numerify('###########'),
            'Time_to_print' => $this->faker->date(),
            'Dimensions' => $this->faker->randomElement(['20x30', '15x25', '10x20']),
            'Paper_type' => $this->faker->randomElement(['Matte', 'Glossy']),
            'Cover_type' => $this->faker->randomElement(['Hardcover', 'Paperback']),
        ];
    }
}
