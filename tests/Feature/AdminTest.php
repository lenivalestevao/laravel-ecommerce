<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_guests_are_sent_to_login_and_customers_are_forbidden(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_pages_load(): void
    {
        $admin = $this->admin();
        Product::factory()->create();
        Order::factory()->create();

        foreach (['dashboard', 'products.index', 'products.create', 'categories.index', 'categories.create',
                  'coupons.index', 'coupons.create', 'orders.index', 'customers.index'] as $name) {
            $this->actingAs($admin)->get(route('admin.'.$name))->assertOk();
        }
    }

    public function test_admin_can_create_update_and_delete_a_product(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Brand New Mug', 'sku' => 'MUG-1', 'category_id' => $category->id,
            'price' => '19.90', 'sale_price' => '15.50', 'stock' => 7, 'is_active' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'MUG-1')->firstOrFail();
        $this->assertSame('brand-new-mug', $product->slug);
        $this->assertSame(1990, $product->price);
        $this->assertSame(1550, $product->sale_price);
        $this->assertFalse($product->is_featured);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Brand New Mug', 'sku' => 'MUG-1', 'price' => '25', 'stock' => 3, 'is_active' => 1, 'is_featured' => 1,
        ])->assertRedirect();
        $this->assertSame(2500, $product->fresh()->price);
        $this->assertNull($product->fresh()->sale_price);
        $this->assertTrue($product->fresh()->is_featured);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));
        $this->assertModelMissing($product);
    }

    public function test_sale_price_must_be_lower_than_the_price(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'Bad', 'sku' => 'BAD-1', 'price' => '10', 'sale_price' => '12', 'stock' => 1,
        ])->assertSessionHasErrors('sale_price');
    }

    public function test_category_and_coupon_crud(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Garden Tools', 'is_active' => 1]);
        $this->assertDatabaseHas('categories', ['slug' => 'garden-tools']);

        $this->actingAs($admin)->post(route('admin.coupons.store'), ['code' => 'spring', 'type' => 'fixed', 'value' => '5.00', 'min_subtotal' => '20', 'is_active' => 1]);
        $this->assertDatabaseHas('coupons', ['code' => 'SPRING', 'value' => 500, 'min_subtotal' => 2000]);

        $this->actingAs($admin)->post(route('admin.coupons.store'), ['code' => 'half', 'type' => 'percent', 'value' => '150'])
            ->assertSessionHasErrors('value');
    }

    public function test_cancelling_an_order_restocks_the_products_once(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $order = Order::factory()->create();
        $order->items()->create(['product_id' => $product->id, 'name' => 'x', 'sku' => 'x', 'price' => 100, 'quantity' => 2, 'total' => 200]);

        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'cancelled']);
        $this->assertSame(5, $product->fresh()->stock);

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'cancelled']);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_admin_can_change_status_and_mark_an_order_paid(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create();

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'shipped'])->assertSessionHas('success');
        $this->assertSame('shipped', $order->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->actingAs($admin)->post(route('admin.orders.paid', $order));
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_orders_can_be_exported_as_csv(): void
    {
        $order = Order::factory()->create(['customer_name' => 'Export Person']);

        $response = $this->actingAs($this->admin())->get(route('admin.orders.export'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString($order->number, $csv);
        $this->assertStringContainsString('Export Person', $csv);
    }
}
