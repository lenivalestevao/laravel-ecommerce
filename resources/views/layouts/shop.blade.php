<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('shop.name') }}</title>
    <meta name="description" content="{{ $description ?? config('shop.tagline') }}">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}">
</head>
<body>
<header class="site-header">
    <div class="container header-row">
        <a class="brand" href="{{ route('home') }}">{{ config('shop.name') }}</a>

        <form class="search" action="{{ route('shop.index') }}" method="get" role="search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products" aria-label="Search products">
            <button type="submit" class="btn">Search</button>
        </form>

        <nav class="top-links" aria-label="Account">
            @auth
                @if (auth()->user()->is_admin)<a href="{{ route('admin.dashboard') }}">Admin</a>@endif
                <a href="{{ route('account.index') }}">My account</a>
                <form method="post" action="{{ route('logout') }}" class="inline">@csrf<button type="submit" class="link">Log out</button></form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
            <a class="cart-link" href="{{ route('cart.index') }}">Cart <span class="count">{{ $cartCount }}</span></a>
        </nav>
    </div>
    <nav class="menu" aria-label="Categories">
        <div class="container">
            <a href="{{ route('shop.index') }}">All products</a>
            @foreach ($menuCategories as $c)
                <a href="{{ route('shop.category', $c) }}">{{ $c->name }}</a>
            @endforeach
        </div>
    </nav>
</header>

<main class="container">
    <x-flash />
    {{ $slot }}
</main>

<footer class="site-footer">
    <div class="container footer-row">
        <span>&copy; {{ date('Y') }} {{ config('shop.name') }}</span>
        <span>
            <a href="{{ route('track') }}">Track an order</a>
            &middot; <a href="mailto:{{ config('shop.contact_email') }}">{{ config('shop.contact_email') }}</a>
        </span>
    </div>
</footer>
</body>
</html>
