<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug()->toString(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->numberBetween(500, 50000),
            'sale_price' => null,
            'stock' => fake()->numberBetween(5, 100),
            'image' => null,
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function onSale(): static
    {
        return $this->state(fn (array $a) => ['sale_price' => (int) ($a['price'] * 0.8)]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
