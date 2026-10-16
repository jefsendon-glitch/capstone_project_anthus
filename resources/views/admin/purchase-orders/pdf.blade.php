<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $order['number'] }}</title>
    <style>
        @page { margin: 38px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
        h1 { color: #075985; font-size: 23px; margin-bottom: 6px; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        thead { display: table-header-group; }
        th { background: #e0f2fe; color: #075985; text-align: left; }
        th, td { padding: 9px 7px; border-bottom: 1px solid #e2e8f0; }
        tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .total { font-size: 13px; font-weight: bold; }
        .notes { white-space: pre-wrap; overflow-wrap: break-word; }
    </style>
</head>
<body>
    <h1>PURCHASE ORDER</h1>
    <p><strong>{{ $order['station']['business_name'] }}</strong><br>
        {{ $order['station']['address'] }}<br>
        {{ $order['station']['contact_number'] }} {{ $order['station']['contact_email'] }}
    </p>
    <p><strong>PO Number:</strong> {{ $order['number'] }}<br>
        <strong>Order Date:</strong> {{ $order['ordered_at'] ?? 'Not specified' }}<br>
        <strong>Expected Delivery:</strong> {{ $order['expected_date'] ?? 'To be confirmed' }}
    </p>
    @if($order['status'] === 'draft')<p class="muted">DRAFT — Not issued to supplier</p>@endif
    <h2>Supplier</h2>
    <p><strong>{{ $order['supplier']['name'] }}</strong><br>
        {{ $order['supplier']['contact_person'] }}<br>
        {{ $order['supplier']['email'] }}<br>
        {{ $order['supplier']['phone'] }}<br>
        {{ $order['supplier']['address'] }}
    </p>
    <table>
        <thead><tr><th>Item</th><th class="right">Quantity</th><th class="right">Unit Cost (PHP)</th><th class="right">Amount (PHP)</th></tr></thead>
        <tbody>
            @foreach($order['items'] as $item)
                <tr><td>{{ $item['name'] }}</td><td class="right">{{ number_format($item['quantity'], 2) }}</td><td class="right">{{ number_format($item['unit_cost'], 2) }}</td><td class="right">{{ number_format($item['amount'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <p class="right total">Total: PHP {{ number_format($order['total'], 2) }}</p>
    @if($order['notes'])<h2>Order Notes</h2><p class="notes">{{ $order['notes'] }}</p>@endif
    <p class="muted">Please confirm availability, pricing, and the delivery schedule with the station.</p>
</body>
</html>
