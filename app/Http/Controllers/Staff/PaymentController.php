<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerPaymentRequest;
use App\Models\User;
use App\Services\CustomerLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly CustomerLedgerService $ledger)
    {
    }

    public function index(Request $request): View
    {
        $customers = User::role('customer')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('credit_balance')
            ->paginate(15)
            ->withQueryString();

        return view('staff.payments.index', compact('customers'));
    }

    public function store(StoreCustomerPaymentRequest $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $this->ledger->recordPayment(
            $customer,
            (float) $request->validated('amount'),
            $request->user(),
            $request->validated('notes'),
        );

        return back()->with('success', 'Payment recorded successfully.');
    }
}
