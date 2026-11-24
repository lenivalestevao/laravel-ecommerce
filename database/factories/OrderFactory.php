<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2000, 40000);

        return [
            'number' => 'LS-'.fake()->unique()->numerify('2026-######'),
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->word(),
            'postcode' => fake()->postcode(),
            'country' => 'United States',
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping' => 800,
            'tax' => 0,
            'total' => $subtotal + 800,
        ];
    }
}
