<x-layouts.app title="Order Details" :heading="'Order Details'">
    <x-card class="mx-auto max-w-xl">
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $order->order_code }}</p>
            <x-delivery-status-badge :status="$order->status" />
        </div>

        <dl class="mt-6 space-y-4 text-sm">
            <div>
                <dt class="text-slate-400">Product</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $order->product_name }} × {{ $order->quantity }} — <span class="font-mono">₱{{ number_format($order->total_amount, 2) }}</span></dd>
            </div>
            <div>
                <dt class="text-slate-400">Delivery Address</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $order->customer_address }}</dd>
            </div>
            <div>
                <dt class="text-slate-400">Preferred Delivery Date</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $order->preferred_delivery_date?->format('F d, Y') ?? '—' }}</dd>
            </div>
        </dl>

        <ol class="mt-6 space-y-3 text-sm">
            @foreach(['pending' => 'Order placed', 'confirmed' => 'Confirmed by staff', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'] as $key => $label)
                @php
                    $steps = ['pending', 'confirmed', 'out_for_delivery', 'delivered'];
                    $reached = in_array($order->status, $steps) && array_search($order->status, $steps) >= array_search($key, $steps);
                @endphp
                <li class="flex items-center gap-3">
                    <span class="flex size-2.5 rounded-full {{ $reached ? 'bg-primary-600' : 'bg-slate-300 dark:bg-slate-700' }}"></span>
                    <span class="{{ $reached ? 'font-medium text-slate-900 dark:text-white' : 'text-slate-400' }}">{{ $label }}</span>
                </li>
            @endforeach
            @if($order->status === 'cancelled')
                <li class="flex items-center gap-3">
                    <span class="flex size-2.5 rounded-full bg-danger-600"></span>
                    <span class="font-medium text-danger-600">Order cancelled</span>
                </li>
            @endif
        </ol>

        <div class="mt-6 flex gap-3">
            <x-button as="a" href="{{ route('customer.orders.index') }}" variant="secondary" class="w-full">Back to My Orders</x-button>

            @can('cancel', $order)
                <form method="POST" action="{{ route('customer.orders.cancel', $order) }}" class="w-full" onsubmit="return confirm('Cancel this order?')">
                    @csrf
                    @method('PATCH')
                    <x-button type="submit" variant="danger" class="w-full">Cancel Order</x-button>
                </form>
            @endcan
        </div>
    </x-card>
</x-layouts.app>
