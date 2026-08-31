<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $recentOrders = $user->deliveryOrders()->with('items')->latest()->take(5)->get();
        $currentOrder = $user->deliveryOrders()->with('items')->whereNotIn('status', ['delivered', 'cancelled'])->latest()->first();
        $recentPayments = $user->payments()->latest('payment_date')->take(5)->get();

        $totalOrders = $user->deliveryOrders()->count();
        $totalSpent = (float) $user->salesTransactions()->sum('total_amount');
        $pendingOrders = $user->deliveryOrders()->whereIn('status', ['pending', 'confirmed', 'out_for_delivery'])->count();

        return view('customer.dashboard', [
            'recentOrders' => $recentOrders,
            'currentOrder' => $currentOrder,
            'recentPayments' => $recentPayments,
            'totalOrders' => $totalOrders,
            'totalSpent' => $totalSpent,
            'pendingOrders' => $pendingOrders,
        ]);
    }
}
