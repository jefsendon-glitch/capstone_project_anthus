<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = $request->user()->payments()->with('recordedBy')->latest('payment_date')->paginate(15);

        return view('customer.payments.index', compact('payments'));
    }
}
