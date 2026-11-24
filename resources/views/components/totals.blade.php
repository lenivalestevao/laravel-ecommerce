@props(['totals'])

<dl class="totals">
    <div><dt>Subtotal</dt><dd>{{ money($totals->subtotal) }}</dd></div>
    @if ($totals->discount > 0)<div><dt>Discount</dt><dd>-{{ money($totals->discount) }}</dd></div>@endif
    <div><dt>Shipping</dt><dd>{{ $totals->shipping > 0 ? money($totals->shipping) : 'Free' }}</dd></div>
    @if ($totals->tax > 0)<div><dt>Tax</dt><dd>{{ money($totals->tax) }}</dd></div>@endif
    <div class="grand"><dt>Total</dt><dd>{{ money($totals->total) }}</dd></div>
</dl>
