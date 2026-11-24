<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Session based shopping cart. It stores only product ids and quantities,
 * and always reads current prices and stock from the database.
 */
class Cart
{
    private const KEY = 'cart.items';
    private const COUPON_KEY = 'cart.coupon';

    public function __construct(private readonly Session $session)
    {
    }

    /** @return array<int, int> product id => quantity */
    public function raw(): array
    {
        return $this->session->get(self::KEY, []);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $items = $this->raw();
        $items[$product->id] = min(($items[$product->id] ?? 0) + $quantity, max($product->stock, 1));
        $this->session->put(self::KEY, $items);
    }

    public function set(Product $product, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($product);

            return;
        }

        $items = $this->raw();
        $items[$product->id] = min($quantity, max($product->stock, 1));
        $this->session->put(self::KEY, $items);
    }

    public function remove(Product $product): void
    {
        $items = $this->raw();
        unset($items[$product->id]);
        $this->session->put(self::KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget([self::KEY, self::COUPON_KEY]);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * Cart lines with their products. Products that were deleted or disabled are dropped.
     *
     * @return Collection<int, object{product: Product, quantity: int, total: int}>
     */
    public function lines(): Collection
    {
        $items = $this->raw();

        if ($items === []) {
            return collect();
        }

        $products = Product::active()->whereKey(array_keys($items))->get()->keyBy('id');

        return collect($items)
            ->filter(fn (int $qty, int $id) => $products->has($id))
            ->map(function (int $qty, int $id) use ($products) {
                $product = $products[$id];

                return (object) [
                    'product' => $product,
                    'quantity' => $qty,
                    'total' => $product->current_price * $qty,
                ];
            })
            ->values();
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('total');
    }

    public function applyCoupon(Coupon $coupon): void
    {
        $this->session->put(self::COUPON_KEY, $coupon->code);
    }

    public function removeCoupon(): void
    {
        $this->session->forget(self::COUPON_KEY);
    }

    /** The coupon in the cart, or null when none was applied or it is no longer valid. */
    public function coupon(): ?Coupon
    {
        $code = $this->session->get(self::COUPON_KEY);

        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        if ($coupon === null || $coupon->problemFor($this->subtotal()) !== null) {
            $this->removeCoupon();

            return null;
        }

        return $coupon;
    }

    public function totals(): Totals
    {
        return Totals::calculate($this->subtotal(), $this->coupon());
    }
}
