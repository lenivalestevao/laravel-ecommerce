<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'type' => Coupon::PERCENT,
            'value' => 10,
            'min_subtotal' => 0,
            'max_uses' => null,
            'used_count' => 0,
            'expires_at' => null,
            'is_active' => true,
        ];
    }
}
