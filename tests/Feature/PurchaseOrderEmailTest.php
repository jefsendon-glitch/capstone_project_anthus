<?php

use App\Mail\SupplierPurchaseOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEmail;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderDocumentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->supplier = Supplier::create(['name' => 'Supplier One', 'email' => 'supplier@example.com']);
    $this->product = Product::factory()->create(['category' => 'container', 'stock_quantity' => 10]);
    $this->order = PurchaseOrder::create([
        'po_number' => 'PO-EMAIL-001', 'supplier_id' => $this->supplier->id,
        'status' => 'ordered', 'ordered_at' => today(), 'expected_date' => today()->addDays(3), 'created_by' => $this->admin->id,
    ]);
    $this->order->items()->create([
        'itemable_type' => Product::class, 'itemable_id' => $this->product->id,
        'quantity_ordered' => 5, 'unit_cost' => 25,
    ]);
    config([
        'mail.default' => 'smtp', 'mail.mailers.smtp.transport' => 'smtp',
        'mail.mailers.smtp.host' => 'smtp.gmail.com', 'mail.mailers.smtp.port' => 587,
        'mail.mailers.smtp.username' => 'station@example.com', 'mail.mailers.smtp.password' => 'test-password',
        'mail.from.address' => 'station@example.com', 'mail.from.name' => 'Water Station',
    ]);
    $this->actingAs($this->admin);
});

function purchaseOrderEmailPayload($test): array
{
    $preview = $test->get(route('admin.purchase-orders.email.preview', $test->order))->assertOk();

    return ['preview_token' => $preview->viewData('previewToken'), 'subject' => 'Purchase Order PO-EMAIL-001',
        'message' => 'Please confirm availability.', 'confirmation' => '1'];
}

test('admin previews and downloads a real PDF without sending mail', function () {
    Mail::fake();
    $this->get(route('admin.purchase-orders.email.preview', $this->order))->assertOk()
        ->assertSee('supplier@example.com')->assertSee('station@example.com')->assertSee('Confirm and Send')
        ->assertSee('x-bind:disabled="sending || !mailConfigured"', false)
        ->assertDontSee('@js(', false);
    $response = $this->get(route('admin.purchase-orders.pdf', $this->order))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
    Mail::assertNothingSent();
});

test('drafts without a supplier email show the required steps before sending', function () {
    $this->order->update(['status' => 'draft']);
    $this->supplier->update(['email' => null]);
    $this->get(route('admin.purchase-orders.show', $this->order))->assertOk()
        ->assertSee('Add Supplier Email')->assertSee('Review and Place Order')
        ->assertSee('This order is a draft.');
});

test('the complete mail pipeline creates the PDF attachment and records the recipient and admin', function () {
    $transport = new ArrayTransport;
    Mail::mailer('smtp')->setSymfonyTransport($transport);
    $payload = purchaseOrderEmailPayload($this);
    $this->post(route('admin.purchase-orders.email.send', $this->order), $payload)
        ->assertRedirect(route('admin.purchase-orders.show', $this->order))->assertSessionHasNoErrors();
    expect($transport->messages())->toHaveCount(1);
    $mail = $transport->messages()->first()->getOriginalMessage();
    expect($mail->getTo()[0]->getAddress())->toBe('supplier@example.com');
    expect($mail->getFrom()[0]->getAddress())->toBe('station@example.com');
    expect($mail->getHtmlBody())->toContain('Please confirm availability.');
    expect($mail->getAttachments())->toHaveCount(1);
    expect($mail->getAttachments()[0]->getPreparedHeaders()->toString())->toContain('application/pdf', 'Purchase-Order-po-email-001.pdf');
    expect($mail->getAttachments()[0]->bodyToString())->toStartWith('JVBER'); // Base64-encoded %PDF.
    $this->assertDatabaseHas('purchase_order_emails', ['purchase_order_id' => $this->order->id,
        'status' => 'sent', 'sent_by' => $this->admin->id, 'recipient' => 'supplier@example.com']);
    expect(PurchaseOrderEmail::first()->sent_at)->not->toBeNull();
    expect($this->product->fresh()->stock_quantity)->toBe(10);
    expect($this->order->fresh()->status)->toBe('ordered');
    $this->get(route('admin.purchase-orders.show', $this->order))->assertOk()->assertSee('Email History (1)')->assertSee('Resend to Supplier');
});

test('duplicate submission sends only one email and resending requires a new confirmed preview', function () {
    Mail::fake();
    $url = route('admin.purchase-orders.email.send', $this->order);
    $payload = purchaseOrderEmailPayload($this);
    $this->post($url, $payload)->assertSessionHasNoErrors();
    $this->post($url, $payload)->assertSessionHasNoErrors();
    Mail::assertSent(SupplierPurchaseOrder::class, 1);
    $newPayload = purchaseOrderEmailPayload($this);
    $this->post($url, $newPayload)->assertSessionHasErrors('resend_confirmation');
    $this->post($url, $newPayload + ['resend_confirmation' => '1'])->assertSessionHasNoErrors();
    Mail::assertSent(SupplierPurchaseOrder::class, 2);
    $this->assertDatabaseCount('purchase_order_emails', 2);
});

test('mail failures are recorded without exposing secrets and a confirmed retry can succeed', function () {
    $payload = purchaseOrderEmailPayload($this);
    Mail::shouldReceive('mailer')->once()->with('smtp')->andThrow(new RuntimeException('secret-password transport failure'));
    $this->post(route('admin.purchase-orders.email.send', $this->order), $payload)->assertSessionHas('error');
    $attempt = PurchaseOrderEmail::first();
    expect($attempt->status)->toBe('failed');
    expect($attempt->failure_reason)->not->toContain('secret-password');
    expect($attempt->sent_at)->toBeNull();
    Mail::swap(new MailManager(app()));
    Mail::fake();
    $this->post(route('admin.purchase-orders.email.send', $this->order), purchaseOrderEmailPayload($this) + ['resend_confirmation' => '1'])
        ->assertSessionHasNoErrors()->assertSessionHas('success');
    Mail::assertSent(SupplierPurchaseOrder::class, 1);
    expect(PurchaseOrderEmail::where('status', 'sent')->count())->toBe(1);
});

test('unconfigured or logging email cannot be reported as sent', function () {
    Mail::fake();
    $payload = purchaseOrderEmailPayload($this);
    config(['mail.default' => 'log']);
    $this->post(route('admin.purchase-orders.email.send', $this->order), $payload)->assertSessionHasErrors('email');
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.password' => null]);
    $this->post(route('admin.purchase-orders.email.send', $this->order), $payload)->assertSessionHasErrors('email');
    $this->get(route('admin.purchase-orders.email.preview', $this->order))->assertSee('Email sending is not configured.');
    Mail::assertNothingSent();
    $this->assertDatabaseCount('purchase_order_emails', 0);
});

test('changed details expired previews invalid recipients and unconfirmed sending are rejected', function () {
    Mail::fake();
    $url = route('admin.purchase-orders.email.send', $this->order);
    $payload = purchaseOrderEmailPayload($this);
    $this->post($url, array_replace($payload, ['confirmation' => '0']))->assertSessionHasErrors('confirmation');
    $this->post($url, array_replace($payload, ['subject' => "Subject\r\nBcc: other@example.com"]))->assertSessionHasErrors('subject');
    $this->supplier->update(['email' => 'changed@example.com']);
    $this->post($url, $payload)->assertSessionHasErrors('preview_token');
    $payload = purchaseOrderEmailPayload($this);
    $this->travel(31)->minutes();
    $this->post($url, $payload)->assertSessionHasErrors('preview_token');
    $this->travelBack();
    $this->supplier->update(['email' => null]);
    $this->get(route('admin.purchase-orders.email.preview', $this->order))->assertSessionHasErrors('email');
    Mail::assertNothingSent();
});

test('only admins can preview send or download purchase order emails', function () {
    Mail::fake();
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->get(route('admin.purchase-orders.email.preview', $this->order))->assertForbidden();
    $this->post(route('admin.purchase-orders.email.send', $this->order), [])->assertForbidden();
    $this->get(route('admin.purchase-orders.pdf', $this->order))->assertForbidden();
    Mail::assertNothingSent();
});

test('draft cancelled and received orders cannot be sent', function (string $status) {
    Mail::fake();
    $payload = purchaseOrderEmailPayload($this);
    $this->order->update(['status' => $status]);
    $this->post(route('admin.purchase-orders.email.send', $this->order), $payload)->assertSessionHasErrors('email');
    Mail::assertNothingSent();
})->with(['draft', 'cancelled', 'received']);

test('an interrupted attempt needs explicit confirmation before another attempt', function () {
    Mail::fake();
    $snapshot = app(PurchaseOrderDocumentService::class)->snapshot($this->order);
    $this->order->emails()->create(['sent_by' => $this->admin->id, 'submission_token' => (string) Str::uuid(),
        'recipient' => 'supplier@example.com', 'sender' => 'station@example.com', 'subject' => 'PO',
        'message' => 'Order', 'order_snapshot' => $snapshot, 'status' => 'sending', 'created_at' => now()->subMinutes(10)]);
    $payload = purchaseOrderEmailPayload($this);
    $url = route('admin.purchase-orders.email.send', $this->order);
    $this->post($url, $payload)->assertSessionHasErrors('resend_confirmation');
    $this->post($url, $payload + ['resend_confirmation' => '1'])->assertSessionHasNoErrors();
    Mail::assertSent(SupplierPurchaseOrder::class, 1);
    $this->assertDatabaseHas('purchase_order_emails', ['status' => 'unconfirmed']);
});

test('an active sending attempt blocks a concurrent email even with resend confirmation', function () {
    Mail::fake();
    $this->order->emails()->create([
        'sent_by' => $this->admin->id, 'submission_token' => (string) Str::uuid(),
        'recipient' => 'supplier@example.com', 'sender' => 'station@example.com', 'subject' => 'PO',
        'message' => 'Order', 'order_snapshot' => app(PurchaseOrderDocumentService::class)->snapshot($this->order),
        'status' => 'sending',
    ]);
    $this->post(route('admin.purchase-orders.email.send', $this->order), purchaseOrderEmailPayload($this) + ['resend_confirmation' => '1'])
        ->assertSessionHasErrors('email');
    Mail::assertNothingSent();
    $this->assertDatabaseCount('purchase_order_emails', 1);
});
