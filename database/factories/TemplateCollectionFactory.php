<?php

namespace Database\Factories;

use App\TemplateCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateCollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TemplateCollection::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'name' => $name,
            'description' => [
                $this->faker->unique()->words(10, true),
                $this->faker->unique()->words(10, true),
                $this->faker->unique()->words(10, true)
            ],
            'preview' => [
                'url' =>  $this->faker->url,
                'selected_scenario' => $this->faker->hexColor,
                'token' => $this->faker->uuid,
                'text' => "Click here to see the preview for this template"
            ],
            'active' => true,
            'default' => false
        ];
    }
}
