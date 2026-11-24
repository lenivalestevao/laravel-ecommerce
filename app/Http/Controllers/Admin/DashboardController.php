<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $counted = Order::where('status', '!=', 'cancelled');

        return view('admin.dashboard', [
            'revenue' => (clone $counted)->where('payment_status', 'paid')->sum('total'),
            'ordersCount' => (clone $counted)->count(),
            'pendingCount' => Order::where('status', 'pending')->count(),
            'customersCount' => User::where('is_admin', false)->count(),
            'lowStock' => Product::where('stock', '<=', 5)->orderBy('stock')->take(8)->get(),
            'recent' => Order::latest()->take(8)->get(),
            'byStatus' => Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
