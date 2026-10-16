<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderDocumentService;
use App\Services\PurchaseOrderMailService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderEmailController extends Controller
{
    public function preview(PurchaseOrder $purchase_order, PurchaseOrderDocumentService $documents, PurchaseOrderMailService $mailer)
    {
        $this->authorize('update', $purchase_order);
        $this->validateOrder($purchase_order);
        $snapshot = $documents->snapshot($purchase_order);
        $token = Crypt::encryptString(json_encode([
            'id' => $purchase_order->id, 'user' => auth()->id(), 'fingerprint' => $documents->fingerprint($snapshot),
            'expires' => now()->addMinutes(30)->timestamp, 'submission_token' => (string) Str::uuid(),
        ], JSON_THROW_ON_ERROR));

        return view('admin.purchase-orders.email-preview', [
            'purchaseOrder' => $purchase_order, 'order' => $snapshot, 'previewToken' => $token,
            'attachmentFilename' => $documents->filename($purchase_order->po_number),
            'mailConfigured' => $mailer->isConfigured(), 'sender' => config('mail.from.address'),
            'isResend' => $purchase_order->emails()->exists(),
            'defaultSubject' => 'Purchase Order '.$purchase_order->po_number.' - Shuanti Water Refilling Station',
            'defaultMessage' => "Dear Supplier,\n\nPlease find our purchase order attached. Kindly confirm item availability, pricing, and the expected delivery date.\n\nThank you,\nShuanti Water Refilling Station",
        ]);
    }

    public function document(PurchaseOrder $purchase_order, PurchaseOrderDocumentService $documents)
    {
        $this->authorize('view', $purchase_order);

        return response($documents->pdf($documents->snapshot($purchase_order)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$documents->filename($purchase_order->po_number).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function send(Request $request, PurchaseOrder $purchase_order, PurchaseOrderDocumentService $documents, PurchaseOrderMailService $mailer)
    {
        $this->authorize('update', $purchase_order);
        $data = $request->validate([
            'preview_token' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:200', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'max:5000'],
            'confirmation' => ['accepted'],
            'resend_confirmation' => ['sometimes', 'accepted'],
        ]);
        try {
            $preview = json_decode(Crypt::decryptString($data['preview_token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            throw ValidationException::withMessages(['preview_token' => 'This email preview is invalid. Open a new preview before sending.']);
        }
        if (($preview['id'] ?? null) !== $purchase_order->id || ($preview['user'] ?? null) !== $request->user()->id
            || ($preview['expires'] ?? 0) < now()->timestamp || ! Str::isUuid($preview['submission_token'] ?? '')) {
            throw ValidationException::withMessages(['preview_token' => 'This preview has expired or is invalid. Open a new preview before sending.']);
        }
        if (! $mailer->isConfigured()) {
            throw ValidationException::withMessages(['email' => 'Email sending is not configured. Set up the station email account before sending.']);
        }
        [$attempt, $created] = DB::transaction(function () use ($request, $purchase_order, $documents, $preview, $data) {
            $order = PurchaseOrder::whereKey($purchase_order->id)->lockForUpdate()->firstOrFail();
            $existing = $order->emails()->where('submission_token', $preview['submission_token'])->first();
            if ($existing) {
                return [$existing, false];
            }
            $this->validateOrder($order);
            $snapshot = $documents->snapshot($order);
            if (! hash_equals($documents->fingerprint($snapshot), $preview['fingerprint'] ?? '')) {
                throw ValidationException::withMessages(['preview_token' => 'The purchase order or supplier details changed. Review a new email preview.']);
            }
            $order->emails()->where('status', 'sending')->where('created_at', '<=', now()->subMinutes(5))->update([
                'status' => 'unconfirmed', 'failure_reason' => 'The previous sending attempt was interrupted. Confirm receipt with the supplier before resending.',
            ]);
            if ($order->emails()->where('status', 'sending')->exists()) {
                throw ValidationException::withMessages(['email' => 'An email is already being processed. Check Email History before sending again.']);
            }
            if ($order->emails()->exists() && ! $request->boolean('resend_confirmation')) {
                throw ValidationException::withMessages(['resend_confirmation' => 'A previous email attempt exists. Review Email History and confirm that another copy should be sent.']);
            }

            return [$order->emails()->create([
                'sent_by' => $request->user()->id, 'submission_token' => $preview['submission_token'],
                'recipient' => $snapshot['supplier']['email'], 'sender' => config('mail.from.address'),
                'subject' => $data['subject'], 'message' => $data['message'], 'order_snapshot' => $snapshot, 'status' => 'sending',
            ]), true];
        });

        if (! $created) {
            return redirect()->route('admin.purchase-orders.show', $purchase_order)->with(
                $attempt->status === 'sent' ? 'success' : 'error',
                $attempt->status === 'sent' ? 'This email was already sent. No duplicate was sent.' : 'This attempt has already been processed. Check Email History and open a new preview to retry.');
        }
        $sent = $mailer->send($attempt);

        return redirect()->route('admin.purchase-orders.show', $purchase_order)->with($sent ? 'success' : 'error', $sent
            ? 'Purchase order sent to '.$attempt->recipient.'. Sending is confirmed by the mail server; supplier acceptance is still pending.'
            : $attempt->fresh()->failure_reason);
    }

    private function validateOrder(PurchaseOrder $order): void
    {
        if (! in_array($order->status, ['ordered', 'partially_received'], true)) {
            throw ValidationException::withMessages(['email' => 'Only ordered or partially received purchase orders can be emailed. Publish a draft before sending.']);
        }
        $order->load('supplier');
        if (! filter_var($order->supplier?->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => 'Add a valid email address to the supplier record before sending.']);
        }
        if (! $order->items()->exists()) {
            throw ValidationException::withMessages(['email' => 'Add at least one item before sending the purchase order.']);
        }
    }
}
