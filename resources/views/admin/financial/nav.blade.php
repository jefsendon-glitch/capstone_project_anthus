<div class="no-print mb-4">
    <p class="mb-3 text-xs text-slate-500">One integrated Admin module</p>
    <nav aria-label="Financial Reports sections" class="flex gap-2 overflow-x-auto pb-1">
        @foreach(['overview' => ['Overview', 'chart'], 'sales' => ['Sales', 'cart'], 'expenses' => ['Expenses', 'clipboard'], 'collections' => ['Collections & Receivables', 'credit-card'], 'exports' => ['Reports & Export', 'archive']] as $key => [$label, $icon])
            <a href="{{ $key === 'overview' ? route('admin.reports.index') : route('admin.financial.section', ['section' => $key]) }}" @if(($section ?? 'overview') === $key) aria-current="page" @endif class="flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium {{ ($section ?? 'overview') === $key ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}"><x-admin-nav-icon :name="$icon" /> {{ $label }}</a>
        @endforeach
    </nav>
</div>
