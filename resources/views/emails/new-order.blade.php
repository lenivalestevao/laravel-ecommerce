<x-mail::message>
# New order {{ $order->number }}

{{ $order->customer_name }} ({{ $order->customer_email }}) placed an order for **{{ money($order->total) }}** with {{ $order->paymentLabel() }}.

<x-mail::button :url="route('admin.orders.show', $order)">
Open in admin
</x-mail::button>
</x-mail::message>
