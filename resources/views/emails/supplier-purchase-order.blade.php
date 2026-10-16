<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ $emailSubject }}</title></head>
<body style="margin:0;background:#f0f9ff;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:24px auto;background:#ffffff;padding:28px;border:1px solid #bae6fd;border-radius:12px;">
        <h1 style="font-size:20px;color:#075985;margin:0 0 16px;">Purchase Order {{ $order['number'] }}</h1>
        <div style="white-space:pre-line;line-height:1.6;">{{ $messageBody }}</div>
        <p style="padding:14px;background:#f0f9ff;line-height:1.6;">
            <strong>Supplier:</strong> {{ $order['supplier']['name'] }}<br>
            <strong>Order Total:</strong> PHP {{ number_format($order['total'], 2) }}<br>
            <strong>Expected Delivery:</strong> {{ $order['expected_date'] ?? 'To be confirmed' }}
        </p>
        <p style="font-size:12px;color:#64748b;">The complete purchase order is attached as a PDF. Please reply to this email to confirm the order.</p>
    </div>
</body>
</html>
