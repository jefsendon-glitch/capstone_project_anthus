@props(['station'])
<x-card padding="p-4">
    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Contact the Station</h2>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $station->business_name }}</p>
    <dl class="mt-3 grid gap-2 text-xs">
        @if($station->contact_number)<div><dt class="text-slate-500">Phone</dt><dd class="mt-0.5 font-medium">{{ $station->contact_number }}</dd></div>@endif
        @if($station->contact_email)<div><dt class="text-slate-500">Email</dt><dd class="mt-0.5 break-all font-medium">{{ $station->contact_email }}</dd></div>@endif
        @if($station->address)<div><dt class="text-slate-500">Station Address</dt><dd class="mt-0.5 font-medium">{{ $station->address }}</dd></div>@endif
    </dl>
    <p class="mt-3 text-xs text-slate-500">Contact the station for payment assistance, account reviews, or gallon return corrections.</p>
</x-card>
