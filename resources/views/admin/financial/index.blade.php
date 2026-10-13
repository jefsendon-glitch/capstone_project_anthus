<x-layouts.app title="Financial Reports" heading="Financial Reports">
    @include('admin.financial.nav')
    @php
        $titles = ['sales' => 'Sales', 'expenses' => 'Expenses', 'collections' => 'Collections & Receivables', 'exports' => 'Reports & Export'];
        $descriptions = ['sales' => 'POS, delivery, cash and credit sales', 'expenses' => 'Expense recording, categories and history', 'collections' => 'Customer payments, balances and overdue credits', 'exports' => 'Report history, daily/monthly reports, printing and export'];
    @endphp
    <div class="mb-4"><h2 class="text-lg font-bold">{{ $titles[$section] }}</h2><p class="text-sm text-slate-500">{{ $descriptions[$section] }}</p></div>
    @if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-danger-50 p-3 text-sm text-danger-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if($section !== 'exports')
        <x-card padding="p-3" class="mb-4">
            <form method="GET" class="grid grid-cols-2 items-end gap-3 md:flex md:flex-wrap">
                <div><x-input-label for="date_from" value="From" /><x-text-input type="date" id="date_from" name="date_from" :value="$from" required /></div>
                <div><x-input-label for="date_to" value="To" /><x-text-input type="date" id="date_to" name="date_to" :value="$to" required /></div>
                @if($section === 'sales')
                    <div><x-input-label for="channel" value="Sales Channel" /><x-select id="channel" name="channel"><option value="">All channels</option><option value="walk-in" @selected(request('channel') === 'walk-in')>POS / Walk-in</option><option value="delivery" @selected(request('channel') === 'delivery')>Delivery</option></x-select></div>
                    <div><x-input-label for="payment_method" value="Payment" /><x-select id="payment_method" name="payment_method"><option value="">All payments</option><option value="cash" @selected(request('payment_method') === 'cash')>Cash</option><option value="loan" @selected(request('payment_method') === 'loan')>Credit</option></x-select></div>
                @elseif($section === 'expenses')
                    <div><x-input-label for="category_filter" value="Category" /><x-select id="category_filter" name="category"><option value="">All categories</option>@foreach($categories as $category)<option @selected(request('category') === $category)>{{ $category }}</option>@endforeach</x-select></div>
                @endif
                <x-button type="submit">Filter</x-button><x-button as="a" :href="route('admin.financial.section', ['section' => $section])" variant="secondary">Reset</x-button>
            </form>
        </x-card>
    @endif
    @include('admin.financial.'.$section)
</x-layouts.app>
