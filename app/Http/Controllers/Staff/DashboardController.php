<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\SalesTransaction;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();

        $mySales = SalesTransaction::where('processed_by', auth()->id());
        $pendingDeliveries = DeliveryOrder::whereIn('status', ['pending', 'confirmed'])->count();
        $outForDelivery = DeliveryOrder::where('status', 'out_for_delivery')->count();
        $salesToday = (float) (clone $mySales)->whereDate('created_at', $today)->sum('total_amount');
        $transactionsToday = (clone $mySales)->whereDate('created_at', $today)->count();
        $lowStockProducts = Product::lowStock()->count();
        $lowStockConsumables = Consumable::lowStock()->count();

        $recentTransactions = (clone $mySales)->with(['customer', 'processedBy'])
            ->latest()
            ->take(8)
            ->get();

        $salesTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($mySales) {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'sales' => (float) (clone $mySales)->whereDate('created_at', $date)->sum('total_amount'),
            ];
        });

        $priorityDeliveries = DeliveryOrder::with('customer')
            ->whereIn('status', ['pending', 'confirmed', 'out_for_delivery'])
            ->orderByRaw("CASE status WHEN 'out_for_delivery' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderBy('preferred_delivery_date')
            ->take(6)
            ->get();

        return view('staff.dashboard', [
            'pendingDeliveries' => $pendingDeliveries,
            'outForDelivery' => $outForDelivery,
            'salesToday' => $salesToday,
            'transactionsToday' => $transactionsToday,
            'lowStockProducts' => $lowStockProducts,
            'lowStockConsumables' => $lowStockConsumables,
            'recentTransactions' => $recentTransactions,
            'salesTrendLabels' => $salesTrend->pluck('label'),
            'salesTrendData' => $salesTrend->pluck('sales'),
            'priorityDeliveries' => $priorityDeliveries,
        ]);
    }
}
