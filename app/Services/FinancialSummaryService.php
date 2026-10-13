<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\SalesTransaction;
use App\Models\User;

class FinancialSummaryService
{
    public function summarize(string $from, string $to): array
    {
        $sales = SalesTransaction::whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        $revenue = (float) (clone $sales)->sum('total_amount');
        $expenses = (float) Expense::whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to)->sum('amount');
        $cashSales = (float) (clone $sales)->where('payment_method', 'cash')->sum('total_amount');
        $collections = (float) CustomerPayment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)->sum('amount');

        return [
            'Sales Revenue' => $revenue,
            'Cash Sales' => $cashSales,
            'Credit Sales' => (float) (clone $sales)->whereIn('payment_method', ['loan', 'credit'])->sum('total_amount'),
            'POS Sales' => (float) (clone $sales)->where('transaction_type', 'walk-in')->sum('total_amount'),
            'Delivery Sales' => (float) (clone $sales)->where('transaction_type', 'delivery')->sum('total_amount'),
            'Recorded Expenses' => $expenses,
            'Sales Less Recorded Expenses' => round($revenue - $expenses, 2),
            'Customer Collections' => $collections,
            'Cash Sales Plus Collections' => round($cashSales + $collections, 2),
            'Current Receivables' => (float) User::role('customer')->where('credit_balance', '>', 0)->sum('credit_balance'),
            'Current Overdue Credit' => (float) SalesTransaction::whereIn('payment_method', ['loan', 'credit'])
                ->whereDate('credit_due_date', '<', today())->whereColumn('total_amount', '>', 'credit_paid_amount')
                ->selectRaw('COALESCE(SUM(total_amount - credit_paid_amount), 0) as balance')->value('balance'),
        ];
    }
}
