<x-shop title="Shopping cart">
    <h1>Shopping cart</h1>

    @if ($lines->isEmpty())
        <p>Your cart is empty. <a href="{{ route('shop.index') }}">Continue shopping</a>.</p>
    @else
        <div class="cart-layout">
            <div class="card">
                <table class="table">
                    <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th class="num">Total</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($lines as $line)
                        <tr>
                            <td><a href="{{ route('shop.show', $line->product) }}">{{ $line->product->name }}</a></td>
                            <td>{{ money($line->product->current_price) }}</td>
                            <td>
                                <form method="post" action="{{ route('cart.update', $line->product) }}" class="qty">
                                    @csrf @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="{{ min($line->product->stock, 99) }}" aria-label="Quantity">
                                    <button class="btn btn-sm" type="submit">Update</button>
                                </form>
                            </td>
                            <td class="num">{{ money($line->total) }}</td>
                            <td>
                                <form method="post" action="{{ route('cart.remove', $line->product) }}">
                                    @csrf @method('DELETE')
                                    <button class="link danger" type="submit" aria-label="Remove {{ $line->product->name }}">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <aside class="card summary">
                <h2>Order summary</h2>

                @if ($coupon)
                    <div class="ok">Coupon <strong>{{ $coupon->code }}</strong> applied
                        <form method="post" action="{{ route('cart.coupon.remove') }}" class="inline">@csrf @method('DELETE')<button class="link" type="submit">remove</button></form>
                    </div>
                @else
                    <form method="post" action="{{ route('cart.coupon.apply') }}" class="coupon">
                        @csrf
                        <input type="text" name="code" placeholder="Coupon code" aria-label="Coupon code" value="{{ old('code') }}">
                        <button class="btn" type="submit">Apply</button>
                    </form>
                    @error('code')<small class="error">{{ $message }}</small>@enderror
                @endif

                <x-totals :totals="$totals" />

                <a class="btn btn-lg btn-block" href="{{ route('checkout.create') }}">Proceed to checkout</a>
            </aside>
        </div>
    @endif
</x-shop>
