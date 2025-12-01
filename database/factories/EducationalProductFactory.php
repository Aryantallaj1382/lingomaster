<?php

namespace Database\Factories;

use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\shop\EducationalProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EducationalProductFactory extends Factory
{
    protected $model = EducationalProduct::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'description' => $this->faker->text(),
            'price' => $this->faker->randomFloat(),
            'discounted_price' => $this->faker->randomFloat(),
            'free_shipping' => $this->faker->boolean(),
            'today_shipping' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'age_group_id' => AgeGroup::factory(),
            'language_id' => Languages::factory(),
        ];
    }
}
