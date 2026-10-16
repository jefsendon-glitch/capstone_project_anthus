# Purchase order email setup

The application sends a PDF purchase order to the email registered on the supplier record. Only administrators can preview, download, send, retry, or resend an order.

## Gmail sender

The local environment is prepared for `shuantiwater@gmail.com`. Enable 2-Step Verification on that Google account and create an [App Password](https://support.google.com/mail/answer/185833). App Password availability depends on the account settings. Do not use the normal Gmail password or share credentials in chat.

Set these values in the local `.env` file (never commit that file):

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=shuantiwater@gmail.com
MAIL_PASSWORD="YOUR_GOOGLE_APP_PASSWORD"
MAIL_FROM_ADDRESS="shuantiwater@gmail.com"
MAIL_FROM_NAME="Shuanti Water Refilling Station"
MAIL_TIMEOUT=30
```

Then run:

```powershell
.runtime\php83\php.exe artisan config:clear
.runtime\php83\php.exe artisan migrate --force
```

MySQL must be running before applying the migration. Internet access to Gmail SMTP is required. Restart any long-running application processes after changing mail configuration. The implementation uses Laravel's [SMTP mail transport and attachments](https://laravel.com/framework/docs/13.x/mail).

## User flow

1. Register the supplier's correct email address.
2. Create and review an **Ordered** purchase order. Drafts must be published first.
3. Open the order and select **Send to Supplier**.
4. Check the fixed recipient, edit the subject/message if needed, and download the PDF for review.
5. Check the review confirmation and select **Confirm and Send**.
6. Review **Email History** for recipient, timestamp, administrator, and status.
7. Receive stock through **Record Receipt** when the delivery arrives. Emailing never increases stock or marks an order received.

**Sent** means the SMTP server accepted the message. It does not prove delivery, reading, or supplier acceptance. If an attempt fails or is interrupted, check with the supplier before retrying. A new preview and explicit confirmation are required for another copy. Active attempts block concurrent sending; attempts interrupted for more than five minutes require confirmation to retry. Reusing a submission token does not send another copy.

History includes the exact order snapshot, subject, message, recipient, and sender for each attempt. Purchase-order reset backs up this history with the order before deleting it.

## Verification

```powershell
.runtime\php83\php.exe vendor/pestphp/pest/bin/pest --compact tests/Feature/PurchaseOrderEmailTest.php
```

Tests generate a real PDF, compose an email with the PDF attachment through Laravel's mail pipeline using an in-memory transport, and cover permissions, duplicate submissions, resends, failures, missing configuration, changed recipients, and expired previews. They do not contact Gmail. Actual delivery must be checked after the App Password is configured, using a supplier address you are authorized to email.
