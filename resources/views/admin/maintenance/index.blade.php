<x-layouts.app title="Maintenance Logs" :heading="'Maintenance Logs'">
    <div class="mb-6 flex justify-end">
        @can('create', \App\Models\MaintenanceLog::class)
            <x-button as="a" href="{{ route('admin.maintenance.create') }}">
                <x-icon name="plus" class="size-4" /> Add Maintenance Log
            </x-button>
        @endcan
    </div>

    <x-card padding="p-0">
        @if($logs->isEmpty())
            <div class="p-6">
                <x-empty-state icon="cog" title="No maintenance logs yet" description="Schedule and track equipment upkeep here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Equipment</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Last Maintenance</th>
                            <th class="px-6 py-3">Next Due</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $log->equipment_name }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $log->category)) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $log->last_maintenance_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $log->next_due_date->format('M d, Y') }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="match($log->status) { 'overdue' => 'danger', 'due_soon' => 'warning', default => 'success' }">
                                        {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('update', $log)
                                            <a href="{{ route('admin.maintenance.edit', $log) }}" class="text-slate-500 hover:text-primary-600">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('delete', $log)
                                            <form method="POST" action="{{ route('admin.maintenance.destroy', $log) }}" onsubmit="return confirm('Delete this maintenance log?')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="text-slate-500 hover:text-danger-600">
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
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
