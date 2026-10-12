@props(['status'])

@php
$colors = [
    'pending' => 'warning',
    'confirmed' => 'secondary',
    'out_for_delivery' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger',
];
$labels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
@endphp

<x-badge :color="$colors[$status] ?? 'slate'">{{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</x-badge>
