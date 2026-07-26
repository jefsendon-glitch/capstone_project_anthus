<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $reportStart = isset($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : Carbon::today()->subDays(6)->startOfDay();
        $reportEnd = isset($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : Carbon::today()->endOfDay();

        $sales = fn () => SalesTransaction::query()
            ->whereBetween('created_at', [$reportStart, $reportEnd]);

        $revenueToday = (float) SalesTransaction::whereDate('created_at', Carbon::today())->sum('total_amount');

        $reportRevenue = (float) $sales()->sum('total_amount');
        $reportTransactions = $sales()->count();
        $averageTransaction = $reportTransactions > 0 ? $reportRevenue / $reportTransactions : 0;

        $paymentTotals = $sales()->selectRaw('payment_method, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $cashVsLoan = collect([
            'cash' => (float) $paymentTotals->get('cash', 0),
            'loan' => (float) $paymentTotals->get('loan', 0) + (float) $paymentTotals->get('credit', 0),
        ]);

        $walkInVsDelivery = $sales()->selectRaw('transaction_type, SUM(total_amount) as total')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        $bestSellers = $sales()->selectRaw('product_name, SUM(quantity) as total_quantity, SUM(total_amount) as total_revenue')
            ->groupBy('product_name')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get();

        $dailyTotals = $sales()->selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as total')
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $trend = collect();
        for ($date = $reportStart->copy(); $date->lte($reportEnd); $date->addDay()) {
            $dateKey = $date->toDateString();

            $trend->push([
                'label' => $date->format('M d'),
                'total' => (float) $dailyTotals->get($dateKey, 0),
            ]);
        }

        return view('admin.reports.index', [
            'business' => Setting::current(),
            'reportStart' => $reportStart,
            'reportEnd' => $reportEnd,
            'revenueToday' => $revenueToday,
            'reportRevenue' => $reportRevenue,
            'reportTransactions' => $reportTransactions,
            'averageTransaction' => $averageTransaction,
            'cashVsLoan' => $cashVsLoan,
            'walkInVsDelivery' => $walkInVsDelivery,
            'bestSellers' => $bestSellers,
            'trendLabels' => $trend->pluck('label'),
            'trendData' => $trend->pluck('total'),
        ]);
    }
}
