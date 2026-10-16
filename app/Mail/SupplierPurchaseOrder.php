<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupplierPurchaseOrder extends Mailable
{
    public function __construct(
        public array $order,
        public string $emailSubject,
        public string $messageBody,
        private string $pdfContents,
        private string $pdfFilename,
        private string $senderAddress,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(from: new Address($this->senderAddress, config('mail.from.name')), subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.supplier-purchase-order');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdfContents, $this->pdfFilename)->withMime('application/pdf')];
    }
}
