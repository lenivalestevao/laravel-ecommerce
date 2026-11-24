<?php

/*
|--------------------------------------------------------------------------
| Store settings
|--------------------------------------------------------------------------
| Everything a store owner usually changes lives here or in .env.
| All products, names and prices in the demo seeder are fictional.
*/

return [

    'name' => env('APP_NAME', 'Laravel Shop'),

    'tagline' => 'Everything you need, delivered to your door',

    // Currency symbol shown next to prices. Prices are stored as integer cents.
    'currency' => env('SHOP_CURRENCY', '$'),

    // Flat shipping fee in cents. Orders above the free threshold ship free.
    'shipping_fee' => (int) env('SHOP_SHIPPING_FEE', 800),
    'free_shipping_over' => (int) env('SHOP_FREE_SHIPPING_OVER', 10000),

    // Tax rate in percent, applied to the subtotal after the discount.
    'tax_rate' => (float) env('SHOP_TAX_RATE', 0),

    'per_page' => 12,

    'payment_methods' => [
        'cod' => 'Cash on delivery',
        'bank' => 'Bank transfer',
    ],

    'order_statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'contact_email' => env('SHOP_EMAIL', 'shop@example.com'),

];
