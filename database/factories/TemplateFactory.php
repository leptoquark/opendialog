<?php

use App\Template;
use Faker\Generator as Faker;

$factory->define(Template::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->words(3, true),
        'description' => $faker->unique()->words(10, true),
        'data' => [
            'id' => 'my_scenario',
            'name' => 'My Scenario',
            'conversations' => [],
            'configurations' => [],
        ],
        'active' => true,
        'platform_id' => sprintf('platform.core.%s', $faker->word)
    ];
});
