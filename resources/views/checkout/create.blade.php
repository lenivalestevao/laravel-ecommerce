<x-shop title="Checkout">
    <h1>Checkout</h1>

    @php($user = auth()->user())

    <form method="post" action="{{ route('checkout.store') }}" class="checkout-layout">
        @csrf
        <div>
            <section class="card pad">
                <h2>Contact and delivery</h2>
                @guest<p class="muted">Have an account? <a href="{{ route('login') }}">Log in</a> to see your orders later.</p>@endguest
                <div class="two">
                    <x-field name="customer_name" label="Full name" :value="$user?->name" required />
                    <x-field name="customer_email" label="Email" type="email" :value="$user?->email" required />
                </div>
                <x-field name="customer_phone" label="Phone" :value="$user?->phone" />
                <x-field name="address" label="Address" required />
                <div class="two">
                    <x-field name="city" label="City" required />
                    <x-field name="state" label="State / region" />
                </div>
                <div class="two">
                    <x-field name="postcode" label="Postcode" required />
                    <x-field name="country" label="Country" required />
                </div>
                <div class="field">
                    <label for="notes">Order notes</label>
                    <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div>
            </section>

            <section class="card pad">
                <h2>Payment method</h2>
                @error('payment_method')<small class="error">{{ $message }}</small>@enderror
                @foreach ($methods as $key => $label)
                    <label class="radio">
                        <input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($methods)) === $key) required>
                        {{ $label }}
                    </label>
                @endforeach
                <p class="muted"><small>Online payment methods send you to the provider's secure page. Bank transfer details are shown after you order.</small></p>
            </section>
        </div>

        <aside class="card summary">
            <h2>Your order</h2>
            <ul class="plain lines">
                @foreach ($lines as $line)
                    <li><span>{{ $line->quantity }} &times; {{ $line->product->name }}</span><span>{{ money($line->total) }}</span></li>
                @endforeach
            </ul>
            <x-totals :totals="$totals" />
            <button class="btn btn-lg btn-block" type="submit">Place order</button>
        </aside>
    </form>
</x-shop>
