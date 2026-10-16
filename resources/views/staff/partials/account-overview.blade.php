<x-card padding="p-4" class="mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-xs text-slate-500">Staff Workspace</p><h2 class="mt-1 text-sm font-semibold">{{ auth()->user()->name }}</h2></div>
        <x-button as="a" :href="route('profile.edit')" variant="info" class="!py-1.5 !text-xs">My Profile</x-button>
    </div>
    <dl class="mt-3 grid gap-3 text-xs sm:grid-cols-3">
        <div><dt class="text-slate-500">Employee ID</dt><dd class="mt-1 font-medium">{{ $staffRecord?->employee_id ?? 'Not assigned' }}</dd></div>
        <div><dt class="text-slate-500">Position</dt><dd class="mt-1 font-medium">{{ $staffRecord?->position ?? 'Staff' }}</dd></div>
        <div><dt class="text-slate-500">My Collections Today</dt><dd class="mt-1 font-semibold text-primary-700 dark:text-primary-300">₱{{ number_format($collectionsToday, 2) }}</dd></div>
    </dl>
</x-card>
