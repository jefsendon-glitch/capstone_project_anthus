<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryFeeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('deliveryFee', [
            'delivery_fee_per_gallon' => ['required', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
        ]);

        Setting::current()->update($validated);

        return back()->with('success', 'Delivery fee per gallon updated.');
    }
}
