<x-mail::message>
# Thank you for your order, {{ $order->customer_name }}!

We received order **{{ $order->number }}**.

<x-mail::table>
| Product | Qty | Total |
| :------ | :-: | ----: |
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ money($item->total) }} |
@endforeach
| **Total** | | **{{ money($order->total) }}** |
</x-mail::table>

Payment: {{ $order->paymentLabel() }}

<x-mail::button :url="route('orders.show', $order)">
View your order
</x-mail::button>

{{ config('shop.name') }}
</x-mail::message>
