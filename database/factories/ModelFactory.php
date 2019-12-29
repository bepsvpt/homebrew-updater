<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */

$factory->define(App\Models\Formula::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'url' => $faker->url,
        'checker' => $faker->randomElement(['Github']),
    ];
});
