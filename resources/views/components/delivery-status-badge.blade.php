@props(['status'])

@php
$colors = [
    'pending' => 'warning',
    'confirmed' => 'secondary',
    'out_for_delivery' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger',
];
@endphp

<x-badge :color="$colors[$status] ?? 'slate'">{{ ucfirst(str_replace('_', ' ', $status)) }}</x-badge>
