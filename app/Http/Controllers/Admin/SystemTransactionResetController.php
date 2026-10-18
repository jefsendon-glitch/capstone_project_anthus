<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemTransactionResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SystemTransactionResetController extends Controller
{
    public function __invoke(Request $request, SystemTransactionResetService $reset): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validateWithBag('systemReset', [
            'reset_confirmation' => ['required', 'in:RESET ALL'],
            'password' => ['required', 'current_password:web'],
            'acknowledge_reset' => ['required', 'accepted'],
        ]);

        try {
            $reset->reset($request->user());
        } catch (ValidationException $exception) {
            throw $exception->errorBag('systemReset');
        }

        return redirect()->route('admin.dashboard')->with('success',
            'All system transactions have been removed. Stock and customer credit balances are now zero. Accounts, product details, suppliers, and station settings are retained. A private backup was saved.');
    }
}
