<?php

namespace Database\Factories;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonorFactory extends Factory
{
    protected $model = Donor::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->company(),
            'created_by' => 1,
            'notes'   => 'Created by DB seeder',
            'tag_color' => $this->faker->hexColor(),
        ];
    }
}