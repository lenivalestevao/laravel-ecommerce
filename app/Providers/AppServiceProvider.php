<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One cart per request, backed by the current session.
        $this->app->scoped(Cart::class, fn ($app) => new Cart($app['session.store']));
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.simple');

        // An order page is visible to its owner, to admins, and to a guest who just placed
        // it or proved ownership on the tracking page (stored in the session).
        Gate::define('view-order', function (?User $user, Order $order) {
            if ($user && ($user->is_admin || $user->id === $order->user_id)) {
                return true;
            }

            return in_array($order->number, session('my_orders', []), true);
        });

        // Header data shared with every storefront view.
        View::composer('layouts.shop', function ($view) {
            $view->with([
                'menuCategories' => Category::active()->orderBy('sort_order')->get(),
                'cartCount' => app(Cart::class)->count(),
            ]);
        });
    }
}
