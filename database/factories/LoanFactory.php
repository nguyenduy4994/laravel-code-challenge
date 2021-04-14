<?php

namespace Database\Factories;

use App\Models\DebitCardTransaction;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'user_id' => fn () => User::factory()->create(),
            'amount' => $this->faker->randomNumber(),
            'terms' => $this->faker->randomElement(Loan::TERMS),
            'outstanding_amount' => $this->faker->randomNumber(),
            'currency_code' => $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
            'processed_at' => $this->faker->date(),
            'status' => Loan::STATUS_DUE
        ];
    }
}
