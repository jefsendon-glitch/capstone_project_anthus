<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($request->input('search', ''));
        $purchases = $request->user()->salesTransactions()->with('processedBy')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('transaction_code', 'like', '%'.$search.'%')->orWhere('product_name', 'like', '%'.$search.'%')))
            ->latest()->orderByDesc('id')->paginate(5)->withQueryString();
        $nextDueDate = $request->user()->salesTransactions()->where('payment_method', 'loan')
            ->whereColumn('credit_paid_amount', '<', 'total_amount')->min('credit_due_date');

        return view('customer.account', ['purchases' => $purchases, 'nextDueDate' => $nextDueDate, 'station' => Setting::current()]);
    }

    public function gallons(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($request->input('search', ''));
        $balances = DB::table('station_gallon_loans as l')->join('station_gallons as g', 'g.id', '=', 'l.station_gallon_id')
            ->where('l.customer_id', $request->user()->id)->where('l.quantity', '>', 0);
        $borrowedCount = (clone $balances)->sum('l.quantity');
        $balances = $balances->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('g.name', 'like', '%'.$search.'%')->orWhere('g.type', 'like', '%'.$search.'%')->orWhere('g.size', 'like', '%'.$search.'%')))
            ->select('g.name', 'g.type', 'g.size', 'l.quantity')->orderBy('g.name')->orderBy('l.id')
            ->paginate(5, ['*'], 'balances_page')->withQueryString();
        $history = DB::table('station_gallon_movements as m')->join('station_gallons as g', 'g.id', '=', 'm.station_gallon_id')
            ->where('m.customer_id', $request->user()->id)
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('g.name', 'like', '%'.$search.'%')->orWhere('g.type', 'like', '%'.$search.'%')->orWhere('m.to_status', 'like', '%'.$search.'%')))
            ->select('g.name', 'g.type', 'g.size', 'm.action', 'm.to_status', 'm.quantity', 'm.created_at')
            ->orderByDesc('m.id')->paginate(5, ['*'], 'history_page')->withQueryString();

        return view('customer.gallons', compact('balances', 'history', 'borrowedCount'));
    }
}
