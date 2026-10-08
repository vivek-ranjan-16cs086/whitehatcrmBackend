<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1, 
            'customer_name' => $this->faker->name(),
            'project_name' => $this->faker->randomElement(['Godrej Woods', 'ATS Homekraft', 'Tata Eureka']),
            'inventory' => $this->faker->randomElement(['2 BHK', '3 BHK', '4 BHK']),
            'phone_no' => $this->faker->phoneNumber(),
            'date' => $this->faker->date(),
        ];
    }
}
