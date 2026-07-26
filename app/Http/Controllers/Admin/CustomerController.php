<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customerQuery = User::role('customer');

        $customerStats = [
            'total' => (clone $customerQuery)->count(),
            'active' => (clone $customerQuery)->where('status', 'active')->count(),
            'withBalance' => (clone $customerQuery)->where('credit_balance', '>', 0)->count(),
            'outstanding' => (float) (clone $customerQuery)->sum('credit_balance'),
        ];

        $customers = $customerQuery
            ->withCount(['deliveryOrders', 'salesTransactions', 'payments'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('balance')->toString() === 'outstanding', fn ($query) => $query->where('credit_balance', '>', 0))
            ->orderByDesc('credit_balance')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'customerStats'));
    }

    public function show(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->load([
            'deliveryOrders' => fn ($query) => $query->latest()->take(10),
            'salesTransactions' => fn ($query) => $query->with('product')->latest()->take(10),
            'payments' => fn ($query) => $query->with('recordedBy')->latest('payment_date')->take(10),
        ]);

        $customerSummary = [
            'salesTotal' => (float) $customer->salesTransactions()->sum('total_amount'),
            'orderTotal' => (float) $customer->deliveryOrders()->sum('total_amount'),
            'paymentTotal' => (float) $customer->payments()->sum('amount'),
        ];

        return view('admin.customers.show', compact('customer', 'customerSummary'));
    }

    public function edit(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->update($request->validated());

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }
}
