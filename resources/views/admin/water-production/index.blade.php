<x-layouts.app title="Water Production" :heading="'Water Production Log'">
    <div class="mb-6 flex justify-end">
        @can('create', \App\Models\WaterProductionLog::class)
            <x-button as="a" href="{{ route('admin.water-production.create') }}">
                <x-icon name="plus" class="size-4" /> Log Production
            </x-button>
        @endcan
    </div>

    <x-card padding="p-0">
        @if($logs->isEmpty())
            <div class="p-6">
                <x-empty-state icon="beaker" title="No production logged yet" description="Log each purified water production batch here to keep inventory in sync." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Gallons Produced</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Logged By</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $log->product->name }} ({{ $log->product->size }})</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-900 dark:text-white">{{ number_format($log->gallons_produced) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $log->production_date->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $log->producedBy?->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-right">
                                    @can('delete', $log)
                                        <form method="POST" action="{{ route('admin.water-production.destroy', $log) }}" onsubmit="return confirm('Remove this log and reverse its stock effect?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-slate-500 hover:text-danger-600">
                                                <x-icon name="trash" class="size-4" />
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
