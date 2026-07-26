<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\DeliveryOrder;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();

        $revenueToday = (float) SalesTransaction::whereDate('created_at', $today)->sum('total_amount');
        $transactionsToday = SalesTransaction::whereDate('created_at', $today)->count();
        $revenueThisWeek = (float) SalesTransaction::where('created_at', '>=', $today->copy()->startOfWeek())->sum('total_amount');
        $revenueThisMonth = (float) SalesTransaction::where('created_at', '>=', $today->copy()->startOfMonth())->sum('total_amount');

        $lowStockProducts = Product::lowStock()->get();
        $lowStockConsumables = Consumable::lowStock()->get();
        $overdueMaintenance = MaintenanceLog::overdue()->count();
        $pendingDeliveryOrders = DeliveryOrder::with('customer')
            ->whereIn('status', ['pending', 'confirmed', 'out_for_delivery'])
            ->orderBy('preferred_delivery_date')
            ->take(5)
            ->get();
        $totalOutstandingCredit = (float) User::role('customer')->sum('credit_balance');

        $trend = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'walkIn' => (float) SalesTransaction::whereDate('created_at', $date)->where('transaction_type', 'walk-in')->sum('total_amount'),
                'delivery' => (float) SalesTransaction::whereDate('created_at', $date)->where('transaction_type', 'delivery')->sum('total_amount'),
            ];
        });

        $recentTransactions = SalesTransaction::with(['customer', 'processedBy'])->latest()->take(5)->get();
        $totalTransactions = SalesTransaction::count();

        $totalCustomers = User::role('customer')->count();
        $gallonsSoldToday = (float) SalesTransaction::whereDate('created_at', $today)
            ->whereHas('product', fn ($query) => $query->where('category', '!=', 'container'))
            ->sum('quantity');

        $activeDeliveriesCount = DeliveryOrder::where('status', 'out_for_delivery')->count();
        $pendingDeliveriesCount = DeliveryOrder::whereIn('status', ['pending', 'confirmed'])->count();

        $waterInventory = Product::where('category', '!=', 'container')->get();
        $bottleInventory = Product::where('category', 'container')->get();

        $topDebtors = User::role('customer')
            ->where('credit_balance', '>', 0)
            ->orderByDesc('credit_balance')
            ->take(5)
            ->get();

        $bestSellers = SalesTransaction::selectRaw('product_name, SUM(quantity) as total_qty, SUM(total_amount) as total_revenue')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $revenueTrendMonthly = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = Carbon::today()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'revenue' => (float) SalesTransaction::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total_amount'),
            ];
        });

        $recentNotifications = auth()->user()->notifications()->latest()->take(6)->get();

        return view('admin.dashboard', [
            'revenueToday' => $revenueToday,
            'transactionsToday' => $transactionsToday,
            'revenueThisWeek' => $revenueThisWeek,
            'revenueThisMonth' => $revenueThisMonth,
            'lowStockProducts' => $lowStockProducts,
            'lowStockConsumables' => $lowStockConsumables,
            'overdueMaintenance' => $overdueMaintenance,
            'pendingDeliveryOrders' => $pendingDeliveryOrders,
            'totalOutstandingCredit' => $totalOutstandingCredit,
            'trendLabels' => $trend->pluck('label'),
            'trendWalkIn' => $trend->pluck('walkIn'),
            'trendDelivery' => $trend->pluck('delivery'),
            'recentTransactions' => $recentTransactions,
            'totalTransactions' => $totalTransactions,
            'totalCustomers' => $totalCustomers,
            'gallonsSoldToday' => $gallonsSoldToday,
            'activeDeliveriesCount' => $activeDeliveriesCount,
            'pendingDeliveriesCount' => $pendingDeliveriesCount,
            'waterInventory' => $waterInventory,
            'bottleInventory' => $bottleInventory,
            'topDebtors' => $topDebtors,
            'bestSellers' => $bestSellers,
            'revenueTrendLabels' => $revenueTrendMonthly->pluck('label'),
            'revenueTrendData' => $revenueTrendMonthly->pluck('revenue'),
            'recentNotifications' => $recentNotifications,
        ]);
    }
}
