<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\FinancialReport;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialController extends Controller
{
    public function index(Request $request, string $section, FinancialSummaryService $summary): View
    {
        abort_unless(in_array($section, ['sales', 'expenses', 'collections', 'exports'], true), 404);
        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'channel' => ['nullable', Rule::in(['walk-in', 'delivery'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'loan'])],
            'category' => ['nullable', Rule::in(Expense::CATEGORIES)],
        ]);
        $from = $filters['date_from'] ?? min(now()->startOfMonth()->toDateString(), $filters['date_to'] ?? today()->toDateString());
        $to = $filters['date_to'] ?? max($from, now()->toDateString());
        $summaryFilters = match ($section) {
            'sales' => array_intersect_key($filters, array_flip(['channel', 'payment_method'])),
            'expenses' => array_intersect_key($filters, array_flip(['category'])),
            default => [],
        };
        $data = ['section' => $section, 'from' => $from, 'to' => $to, 'summary' => $summary->summarize($from, $to, $summaryFilters)];

        if ($section === 'sales') {
            $data['sales'] = SalesTransaction::where('created_at', '>=', $from.' 00:00:00')->where('created_at', '<', Carbon::parse($to)->addDay()->startOfDay())
                ->when($filters['channel'] ?? null, fn ($q, $value) => $q->where('transaction_type', $value))
                ->when($filters['payment_method'] ?? null, fn ($q, $value) => $value === 'loan' ? $q->whereIn('payment_method', ['loan', 'credit']) : $q->where('payment_method', $value))
                ->latest()->paginate(15)->withQueryString();
        } elseif ($section === 'expenses') {
            $data['expenses'] = Expense::with('recordedBy')->whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to)
                ->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value))
                ->orderByDesc('expense_date')->orderByDesc('id')->paginate(15)->withQueryString();
            $data['categories'] = Expense::CATEGORIES;
        } elseif ($section === 'collections') {
            $data['payments'] = CustomerPayment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)->orderByDesc('payment_date')->orderByDesc('id')->paginate(15, ['*'], 'payments_page')->withQueryString();
            $data['customers'] = User::role('customer')->where('credit_balance', '>', 0)->orderByDesc('credit_balance')->paginate(15, ['*'], 'customers_page')->withQueryString();
            $data['overdue'] = SalesTransaction::with('customer')->whereIn('payment_method', ['loan', 'credit'])
                ->whereDate('credit_due_date', '<', today())->whereColumn('total_amount', '>', 'credit_paid_amount')
                ->orderBy('credit_due_date')->paginate(15, ['*'], 'overdue_page')->withQueryString();
        } else {
            $data['reports'] = FinancialReport::with('generatedBy')->latest()->paginate(15)->withQueryString();
        }

        return view('admin.financial.index', $data);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        Expense::create($data + ['recorded_by' => $request->user()->id]);

        return redirect()->route('admin.financial.section', ['section' => 'expenses', 'date_from' => $data['expense_date'], 'date_to' => $data['expense_date']])->with('success', 'Expense recorded successfully.');
    }

    public function resetCollections(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'reset_confirmation' => ['required', 'in:RESET'],
        ]);
        $count = DB::transaction(function () use ($request, $data) {
            $payments = CustomerPayment::whereDate('payment_date', '>=', $data['date_from'])
                ->whereDate('payment_date', '<=', $data['date_to'])->orderBy('id')->lockForUpdate()->get(['id', 'amount']);
            foreach ($payments->pluck('id')->chunk(500) as $ids) {
                CustomerPayment::whereIn('id', $ids)->delete();
            }
            activity('customer_payments')->causedBy($request->user())->event('reset')
                ->withProperties([
                    'date_from' => $data['date_from'], 'date_to' => $data['date_to'],
                    'payments_deleted' => $payments->count(), 'amount_removed' => $payments->sum('amount'),
                ])->log('Reset collection payment history for selected period');

            return $payments->count();
        });

        return redirect()->route('admin.financial.section', [
            'section' => 'collections', 'date_from' => $data['date_from'], 'date_to' => $data['date_to'],
        ])->with('success', "{$count} payment records deleted for the selected period. Collection totals and history updated. Customer balances, paid credit amounts, and saved reports preserved.");
    }

    public function generate(Request $request, FinancialSummaryService $summary): RedirectResponse
    {
        $data = $request->validate([
            'period_type' => ['required', Rule::in(['daily', 'monthly', 'custom'])],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required_if:period_type,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $from = Carbon::parse($data['date_from']);
        $to = match ($data['period_type']) {
            'monthly' => $from->copy()->endOfMonth(),
            'daily' => $from->copy(),
            default => Carbon::parse($data['date_to']),
        };
        if ($data['period_type'] === 'monthly') $from->startOfMonth();
        $report = FinancialReport::create([
            'period_type' => $data['period_type'], 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString(),
            'summary' => $summary->summarize($from->toDateString(), $to->toDateString()), 'generated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.financial.reports.show', $report)->with('success', 'Financial report saved to report history.');
    }

    public function show(FinancialReport $report): View
    {
        return view('admin.financial.report', compact('report'));
    }

    public function destroyReport(FinancialReport $report, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $report->delete();

        return redirect()->route('admin.financial.section', ['section' => 'exports'])
            ->with('success', 'Saved financial report deleted. Sales, expenses, payments, and customer balances preserved.');
    }

    public function export(FinancialReport $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Financial Report', 'FR-'.$report->id]);
            fputcsv($stream, ['Period', $report->date_from->toDateString(), $report->date_to->toDateString()]);
            fputcsv($stream, ['Generated', $report->created_at->toDateTimeString()]);
            fputcsv($stream, ['Metric', 'Amount (PHP)']);
            foreach ($report->summary as $label => $amount) fputcsv($stream, [$label, number_format((float) $amount, 0, '.', '')]);
            fclose($stream);
        }, 'financial-report-'.$report->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
