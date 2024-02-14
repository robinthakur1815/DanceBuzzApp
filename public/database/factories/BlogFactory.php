<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Blog;
use Faker\Generator as Faker;

$factory->define(Blog::class, function (Faker $faker) {
    return [
        'title' => $faker->text(50),
        'published_content' => $faker->text(200),
        'saved_content' => $faker->text(200),
        'app_id' => $faker->integer(5),
        'created_by' => $faker->integer(6),
        'updated_by' => $faker->integer(6),

    ];
});
