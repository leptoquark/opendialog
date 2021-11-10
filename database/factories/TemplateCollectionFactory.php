<?php

use App\Template;
use Faker\Generator as Faker;

$factory->define(\App\TemplateCollection::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->words(3, true),
        'description' => [
            $faker->unique()->words(10, true),
            $faker->unique()->words(10, true),
            $faker->unique()->words(10, true)
        ],
        'preview' => [
            'url' =>  $faker->url,
            'selected_scenario' => $faker->hexColor,
            'token' => $faker->uuid
        ],
        'active' => true,
    ];
});
