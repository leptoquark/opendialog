<?php

namespace Database\Factories;

use App\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Template::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->unique()->words(10, true),
            'data' => [
                'id' => 'my_scenario',
                'name' => 'My Scenario',
                'od_id' => 'my_scenario',
                'conversations' => [],
                'configurations' => [],
            ],
            'active' => true,
            'platform_id' => sprintf('platform.core.%s', $this->faker->word)

        ];
    }
}
