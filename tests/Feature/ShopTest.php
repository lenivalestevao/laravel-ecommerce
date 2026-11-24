<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_featured_products(): void
    {
        $product = Product::factory()->featured()->create(['name' => 'Blue Lamp']);

        $this->get('/')->assertOk()->assertSee('Blue Lamp');
    }

    public function test_hidden_products_are_not_listed_or_reachable(): void
    {
        $product = Product::factory()->create(['name' => 'Secret Item', 'is_active' => false]);

        $this->get(route('shop.index'))->assertDontSee('Secret Item');
        $this->get(route('shop.show', $product))->assertNotFound();
    }

    public function test_catalog_filters_by_category_and_search(): void
    {
        $shoes = Category::factory()->create(['name' => 'Shoes']);
        $hats = Category::factory()->create(['name' => 'Hats']);
        Product::factory()->create(['category_id' => $shoes->id, 'name' => 'Trail Runner']);
        Product::factory()->create(['category_id' => $hats->id, 'name' => 'Straw Hat']);

        $this->get(route('shop.category', $shoes))->assertSee('Trail Runner')->assertDontSee('Straw Hat');
        $this->get(route('shop.index', ['q' => 'Straw']))->assertSee('Straw Hat')->assertDontSee('Trail Runner');
    }

    public function test_catalog_sorts_by_price(): void
    {
        Product::factory()->create(['name' => 'Cheap One', 'price' => 1000]);
        Product::factory()->create(['name' => 'Pricey One', 'price' => 9000]);

        $this->get(route('shop.index', ['sort' => 'price_desc']))
            ->assertSeeInOrder(['Pricey One', 'Cheap One']);
    }

    public function test_product_page_shows_sale_price(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'sale_price' => 7500]);

        $this->get(route('shop.show', $product))->assertOk()->assertSee('$75.00')->assertSee('$100.00');
    }

    public function test_invalid_sort_is_rejected(): void
    {
        $this->get(route('shop.index', ['sort' => 'nonsense']))->assertSessionHasErrors('sort');
    }
}
