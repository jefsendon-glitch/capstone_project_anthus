<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gallon Movement Report — {{ $monthLabel }}</title>
    <style>
        body { margin: 24px auto; padding: 0 16px; max-width: 1200px; font: 14px Arial, sans-serif; color: #1e293b; }
        h1 { margin-bottom: 6px; font-size: 22px; }
        p { line-height: 1.5; }
        .tools { display: flex; gap: 12px; align-items: center; margin-bottom: 20px; }
        button, .tools a { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: white; color: #1e293b; cursor: pointer; text-decoration: none; }
        .summary { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
        .summary span { border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #cbd5e1; overflow-wrap: anywhere; }
        th { background: #f1f5f9; }
        @media print {
            @page { size: A4 landscape; margin: 12mm; }
            body { margin: 0; padding: 0; max-width: none; color: black; }
            .tools { display: none; }
            .table-wrap { overflow: visible; }
            table { table-layout: fixed; font-size: 10px; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="tools">
        <a href="{{ route('admin.inventory.gallon-inventory', ['month' => $month]) }}">Back to Gallon Inventory</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>
    <h1>Gallon Movement Report — {{ $monthLabel }}</h1>
    <p>Shuanti Water Refilling Station<br>Generated {{ now('Asia/Manila')->format('M d, Y g:i A') }} (Asia/Manila) · {{ number_format($movements->count()) }} movement records</p>
    <div class="summary">
        @foreach($summary as $action => $quantity)
            <span>{{ ucfirst($action) }}: <strong>{{ number_format($quantity) }}</strong> gallons</span>
        @endforeach
    </div>
    <p>Quantities summarize movements recorded during this month. A container may appear in multiple movements; these are not month-end stock balances.</p>
    <div class="table-wrap">
        <table>
            <thead><tr>@foreach(['Date', 'Gallon / Size', 'Action', 'Movement', 'Quantity', 'Customer', 'Recorded By', 'Notes'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at }}</td>
                        <td>{{ $movement->gallon_name }} / {{ $movement->gallon_size }}</td>
                        <td>{{ ucfirst($movement->action) }}</td>
                        <td>{{ $movement->from_status ?? 'New' }} → {{ $movement->to_status }}</td>
                        <td>{{ number_format($movement->quantity) }}</td>
                        <td>{{ $movement->customer_name ?? '—' }}</td>
                        <td>{{ $movement->recorded_by_name }}</td>
                        <td>{{ $movement->notes }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No gallon movements recorded for {{ $monthLabel }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
