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
    $payload = ['expense_date' => today()->toDateString(), 'category' => 'Utilities', 'description' => 'Electricity bill', 'amount' => 250.50];
    $this->actingAs($this->admin)->post(route('admin.financial.expenses.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('expenses', ['description' => 'Electricity bill', 'amount' => 250.50, 'recorded_by' => $this->admin->id]);
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
