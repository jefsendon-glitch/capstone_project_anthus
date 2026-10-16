<?php

namespace App\Services;

use App\Mail\SupplierPurchaseOrder;
use App\Models\PurchaseOrderEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PurchaseOrderMailService
{
    public function isConfigured(): bool
    {
        return config('mail.default') === 'smtp'
            && config('mail.mailers.smtp.transport') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL);
    }

    public function send(PurchaseOrderEmail $attempt): bool
    {
        try {
            $documents = app(PurchaseOrderDocumentService::class);
            $mail = new SupplierPurchaseOrder($attempt->order_snapshot, $attempt->subject, $attempt->message,
                $documents->pdf($attempt->order_snapshot), $documents->filename($attempt->order_snapshot['number']), $attempt->sender);
            Mail::mailer('smtp')->to($attempt->recipient)->send($mail);
        } catch (Throwable $exception) {
            // SMTP failures may contain credentials or provider responses. Keep them out of the UI and audit record.
            $attempt->update(['status' => 'failed', 'failure_reason' => 'Sending was not confirmed. Check the station email settings and confirm with the supplier before retrying.']);
            Log::warning('Purchase order email failed.', ['attempt_id' => $attempt->id, 'exception_type' => get_class($exception)]);

            return false;
        }

        // Do not classify a database error after SMTP acceptance as a mail failure: automatic retries could duplicate an email.
        $attempt->update(['status' => 'sent', 'sent_at' => now(), 'failure_reason' => null]);

        return true;
    }
}
