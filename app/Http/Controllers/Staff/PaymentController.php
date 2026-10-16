<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerPaymentRequest;
use App\Models\User;
use App\Models\CustomerPayment;
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
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'history_search' => ['nullable', 'string', 'max:100'],
            'history_from' => ['nullable', 'date_format:Y-m-d'],
            'history_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('history_from') ? ['after_or_equal:history_from'] : [])],
        ]);
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

        $collections = CustomerPayment::with(['customer', 'recordedBy'])
            ->when($request->user()->isStaff(), fn ($query) => $query->where('recorded_by', $request->user()->id))
            ->when($request->filled('history_search'), function ($query) use ($request) {
                $search = trim($request->input('history_search'));
                $query->where(fn ($query) => $query->where('customer_name', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%')));
            })
            ->when($request->filled('history_from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->input('history_from')))
            ->when($request->filled('history_to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->input('history_to')))
            ->latest('payment_date')->latest('id')->paginate(5, ['*'], 'collections_page')->withQueryString();

        return view('staff.payments.index', compact('customers', 'collections'));
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
