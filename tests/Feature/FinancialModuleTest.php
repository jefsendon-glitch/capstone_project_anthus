<?php

use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\FinancialReport;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('all integrated financial sections render and reject non admins', function () {
    foreach (['sales', 'expenses', 'collections', 'exports'] as $section) {
        $this->actingAs($this->admin)->get(route('admin.financial.section', compact('section')))
            ->assertOk()->assertSee('One integrated Admin module');
    }
    $user = User::factory()->create();
    foreach (['sales', 'expenses', 'collections', 'exports'] as $section) {
        $this->actingAs($user)->get(route('admin.financial.section', compact('section')))->assertForbidden();
    }
    $this->post(route('admin.financial.expenses.store'), [])->assertForbidden();
    $this->post(route('admin.financial.reports.generate'), [])->assertForbidden();
});

test('admin records expenses and invalid amounts are rejected', function () {
    $payload = ['expense_date' => today()->toDateString(), 'category' => 'Utilities', 'description' => 'Electricity bill', 'amount' => 250];
    $this->actingAs($this->admin)->post(route('admin.financial.expenses.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('expenses', ['description' => 'Electricity bill', 'amount' => 250, 'recorded_by' => $this->admin->id]);
    $payload['amount'] = -1;
    $this->post(route('admin.financial.expenses.store'), $payload)->assertSessionHasErrors('amount');
    $this->assertDatabaseCount('expenses', 1);
});

test('summary separates collections from sales and reports retain original totals', function () {
    $customer = User::factory()->create(['credit_balance' => 60]);
    $customer->assignRole('customer');
    $product = Product::factory()->create();
    foreach (['cash' => 100, 'loan' => 60] as $method => $amount) {
        SalesTransaction::create([
            'transaction_code' => 'FIN-'.$method, 'transaction_type' => $method === 'cash' ? 'walk-in' : 'delivery',
            'product_id' => $product->id, 'product_name' => $product->name,
            'customer_id' => $customer->id, 'quantity' => 1, 'unit_price' => $amount, 'total_amount' => $amount,
            'payment_method' => $method, 'credit_due_date' => today()->subDay(), 'credit_paid_amount' => 0,
        ]);
    }
    Expense::create(['expense_date' => today(), 'category' => 'Utilities', 'description' => 'Bill', 'amount' => 20]);
    CustomerPayment::create(['customer_id' => $customer->id, 'customer_name' => $customer->name, 'amount' => 10, 'payment_date' => today()]);
    $summary = app(FinancialSummaryService::class)->summarize(today()->toDateString(), today()->toDateString());
    expect($summary['Sales Revenue'])->toBe(160.0)
        ->and($summary['Cash Sales Plus Collections'])->toBe(110.0)
        ->and($summary['Sales Less Recorded Expenses'])->toBe(140.0)
        ->and($summary['Current Overdue Credit'])->toBe(60.0);
    $this->actingAs($this->admin)->post(route('admin.financial.reports.generate'), [
        'period_type' => 'monthly', 'date_from' => today()->toDateString(),
    ])->assertSessionHasNoErrors();
    $report = FinancialReport::firstOrFail();
    expect($report->date_from->day)->toBe(1)->and((float) $report->summary['Sales Revenue'])->toBe(160.0);
    Expense::create(['expense_date' => today(), 'category' => 'Other', 'description' => 'New expense', 'amount' => 5]);
    expect((float) $report->fresh()->summary['Recorded Expenses'])->toBe(20.0);
    $this->get(route('admin.financial.reports.show', $report))->assertOk()->assertSee('Print / Save PDF');
    $this->get(route('admin.financial.reports.export', $report))->assertOk()
        ->assertDownload('financial-report-'.$report->id.'.csv');
    $this->actingAs(User::factory()->create())->get(route('admin.financial.reports.export', $report))->assertForbidden();
});

test('custom reports and financial filters reject reversed date ranges', function () {
    $this->actingAs($this->admin)->post(route('admin.financial.reports.generate'), [
        'period_type' => 'custom', 'date_from' => '2026-10-13', 'date_to' => '2026-10-01',
    ])->assertSessionHasErrors('date_to');
    $this->get(route('admin.financial.section', ['section' => 'sales', 'date_from' => '2026-10-13', 'date_to' => '2026-10-01']))
        ->assertSessionHasErrors('date_to');
    $this->assertDatabaseCount('financial_reports', 0);
});

test('financial sales cards reconcile to filtered transaction rows', function () {
    $product = Product::factory()->create();
    foreach (['cash' => 10.01, 'loan' => 20.02] as $method => $amount) {
        SalesTransaction::create(['transaction_code' => 'RECON-'.$method,
            'transaction_type' => $method === 'cash' ? 'walk-in' : 'delivery',
            'product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1,
            'unit_price' => $amount, 'total_amount' => $amount, 'payment_method' => $method]);
    }
    $this->actingAs($this->admin)->get(route('admin.financial.section', [
        'section' => 'sales', 'channel' => 'walk-in', 'payment_method' => 'cash',
    ]))->assertOk()->assertViewHas('sales', fn ($rows) => $rows->total() === 1)
        ->assertViewHas('summary', fn ($totals) => $totals['Sales Revenue'] === 10.01
            && $totals['Cash Sales'] === 10.01 && $totals['Credit Sales'] === 0.0);
    Expense::create(['expense_date' => today(), 'category' => 'Utilities', 'description' => 'Water', 'amount' => 12.34]);
    Expense::create(['expense_date' => today(), 'category' => 'Other', 'description' => 'Other', 'amount' => 9.99]);
    $this->get(route('admin.financial.section', ['section' => 'expenses', 'category' => 'Utilities']))
        ->assertOk()->assertViewHas('expenses', fn ($rows) => $rows->total() === 1)
        ->assertViewHas('summary', fn ($totals) => $totals['Recorded Expenses'] === 12.34);
});

test('one sided financial dates produce valid effective report ranges', function () {
    $this->actingAs($this->admin)->get(route('admin.financial.section', [
        'section' => 'sales', 'date_to' => '2020-01-01',
    ]))->assertOk()->assertViewHas('from', '2020-01-01')->assertViewHas('to', '2020-01-01');
    $future = today()->addDays(10)->toDateString();
    $this->get(route('admin.reports.index', ['date_from' => $future]))->assertOk()
        ->assertViewHas('reportEnd', fn ($end) => $end->toDateString() === $future);
});

test('collection forms accept whole amounts and reject decimal payments', function () {
    $customer = User::factory()->create(['credit_balance' => 100]);
    $customer->assignRole('customer');
    $this->actingAs($this->admin)->post(route('payments.store', $customer), ['amount' => '10.005'])
        ->assertSessionHasErrors('amount');
    $this->post(route('payments.store', $customer), ['amount' => '10.01'])->assertSessionHasErrors('amount');
    $this->post(route('payments.store', $customer), ['amount' => '10'])->assertSessionHasNoErrors();
    expect((float) $customer->fresh()->credit_balance)->toBe(90.0);
    $this->assertDatabaseCount('customer_payments', 1);
    $this->assertDatabaseHas('customer_payments', ['customer_id' => $customer->id, 'amount' => 10]);
});

test('only admins can delete saved reports without deleting financial source records', function () {
    $expense = Expense::create(['expense_date' => today(), 'category' => 'Utilities', 'description' => 'Bill', 'amount' => 20]);
    $report = FinancialReport::create([
        'period_type' => 'daily', 'date_from' => today(), 'date_to' => today(),
        'summary' => ['Recorded Expenses' => 20], 'generated_by' => $this->admin->id,
    ]);
    $user = User::factory()->create();
    $this->actingAs($user)->delete(route('admin.financial.reports.destroy', $report))->assertForbidden();
    $this->assertDatabaseHas('financial_reports', ['id' => $report->id]);
    $this->actingAs($this->admin)->get(route('admin.financial.section', ['section' => 'exports']))
        ->assertOk()->assertSee('Delete saved report');
    $this->delete(route('admin.financial.reports.destroy', $report))
        ->assertRedirect(route('admin.financial.section', ['section' => 'exports']));
    $this->assertDatabaseMissing('financial_reports', ['id' => $report->id]);
    $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    $this->get(route('admin.financial.reports.show', $report->id))->assertNotFound();
});

test('collections allocate to unpaid legacy credit and reconcile reports without counting revenue twice', function () {
    $customer = User::factory()->create(['credit_balance' => 50.30]);
    $customer->assignRole('customer');
    $product = Product::factory()->create();
    $createCredit = function (string $code, float $amount, ?string $due, string $status) use ($customer, $product) {
        return SalesTransaction::create(['transaction_code' => $code, 'transaction_type' => 'walk-in',
            'customer_id' => $customer->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 1, 'unit_price' => $amount, 'total_amount' => $amount, 'payment_method' => 'loan',
            'credit_paid_amount' => 0, 'credit_due_date' => $due, 'credit_status' => $status]);
    };
    $undated = $createCredit('UNDATED', 20.20, null, 'not_applicable');
    $overdue = $createCredit('LEGACY-DUE', 30.10, today()->subDay()->toDateString(), 'not_applicable');
    app(\App\Services\CustomerLedgerService::class)->recordPayment($customer, 35.15, $this->admin);
    expect((float) $overdue->fresh()->credit_paid_amount)->toBe(30.10)
        ->and($overdue->fresh()->credit_status)->toBe('paid')
        ->and((float) $undated->fresh()->credit_paid_amount)->toBe(5.05)
        ->and((float) $customer->fresh()->credit_balance)->toBe(15.15);
    $summary = app(FinancialSummaryService::class)->summarize(today()->toDateString(), today()->toDateString());
    expect($summary['Sales Revenue'])->toBe(50.30)->and($summary['Customer Collections'])->toBe(35.15)
        ->and($summary['Cash Sales Plus Collections'])->toBe(35.15)
        ->and($summary['Current Overdue Credit'])->toBe(0.0)
        ->and($summary['Tracked Unpaid Credit'])->toBe(15.15)
        ->and($summary['Receivables Reconciliation Difference'])->toBe(0.0);
    app(\App\Services\CustomerLedgerService::class)->recordPayment($customer, 15.15, $this->admin);
    expect((float) $customer->fresh()->credit_balance)->toBe(0.0)
        ->and((float) $undated->fresh()->credit_paid_amount)->toBe(20.20);
});

test('fractional unit prices accept exact cash tender without floating point shortages', function () {
    $product = Product::factory()->create(['unit_price' => 0.10, 'stock_quantity' => 10]);
    $sales = app(\App\Services\SalesService::class)->recordSale([
        'items' => [['product_id' => $product->id, 'quantity' => 3]], 'payment_method' => 'cash',
        'transaction_type' => 'walk-in', 'tendered_amount' => 0.30,
    ], $this->admin);
    expect((float) $sales->first()->total_amount)->toBe(0.30)
        ->and((float) $sales->first()->change_amount)->toBe(0.0);
    $summary = app(FinancialSummaryService::class)->summarize(today()->toDateString(), today()->toDateString());
    expect($summary['Sales Revenue'])->toBe(0.30)->and($summary['Cash Sales'])->toBe(0.30);
});

test('financial reports flag unmatched opening balances without changing customer accounts', function () {
    $customer = User::factory()->create(['credit_balance' => 123.45]);
    $customer->assignRole('customer');
    $this->actingAs($this->admin)->get(route('admin.financial.section', ['section' => 'collections']))
        ->assertOk()->assertSee('Balance Reconciliation Needed')
        ->assertViewHas('summary', fn ($summary) => $summary['Receivables Reconciliation Difference'] === 123.45);
    expect((float) $customer->fresh()->credit_balance)->toBe(123.45);
});

test('stale delivery requests cannot cancel or duplicate a completed financial transaction', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $product = Product::factory()->create(['unit_price' => 25, 'stock_quantity' => 10]);
    $service = app(\App\Services\DeliveryService::class);
    $order = $service->placeOrder(['product_id' => $product->id, 'quantity' => 2,
        'customer_address' => 'Delivery address', 'payment_method' => 'cash'], $customer);
    $service->updateStatus($order, 'confirmed', $this->admin);
    $staleOrder = $order->fresh();
    $service->fulfill($order, 'cash', $this->admin);
    foreach (['cancel', 'status', 'fulfill'] as $action) {
        try {
            match ($action) {
                'cancel' => $service->cancel($staleOrder, $this->admin),
                'status' => $service->updateStatus($staleOrder, 'cancelled', $this->admin),
                'fulfill' => $service->fulfill($staleOrder, 'cash', $this->admin),
            };
            $this->fail('A completed order must reject stale state changes.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            expect($exception->errors())->not->toBeEmpty();
        }
    }
    expect($order->fresh()->status)->toBe('delivered')->and((float) $product->fresh()->stock_quantity)->toBe(8.0);
    $this->assertDatabaseCount('sales_transactions', 1);
    $summary = app(FinancialSummaryService::class)->summarize(today()->toDateString(), today()->toDateString());
    expect($summary['Sales Revenue'])->toBe(50.0);
});
