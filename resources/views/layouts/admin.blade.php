<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ isset($title) ? $title.' | ' : '' }}Admin | {{ config('shop.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}">
</head>
<body class="admin">
<aside class="sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}">{{ config('shop.name') }}</a>
    <nav aria-label="Admin">
        @php
        $items = [
            'admin.dashboard' => 'Dashboard',
            'admin.orders.index' => 'Orders',
            'admin.products.index' => 'Products',
            'admin.categories.index' => 'Categories',
            'admin.customers.index' => 'Customers',
            'admin.coupons.index' => 'Coupons',
        ];
        @endphp
        @foreach ($items as $route => $label)
            <a href="{{ route($route) }}" @class(['active' => request()->routeIs(str_replace('.index', '.*', $route))])>{{ $label }}</a>
        @endforeach
        <a href="{{ route('home') }}" target="_blank" rel="noopener">View store &nearr;</a>
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="link">Log out</button></form>
    </nav>
</aside>
<main class="admin-main">
    <h1>{{ $title ?? 'Admin' }}</h1>
    <x-flash />
    {{ $slot }}
</main>
</body>
</html>
