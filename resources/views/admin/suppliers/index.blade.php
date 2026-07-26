<x-layouts.app title="Suppliers" :heading="'Suppliers'">
    <div class="mb-6 flex justify-end">
        @can('create', \App\Models\Supplier::class)
            <x-button as="a" href="{{ route('admin.suppliers.create') }}">
                <x-icon name="plus" class="size-4" /> Add Supplier
            </x-button>
        @endcan
    </div>

    <x-card padding="p-0">
        @if($suppliers->isEmpty())
            <div class="p-6">
                <x-empty-state icon="building" title="No suppliers yet" description="Add the vendors you buy water supplies and gallons from here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Contact Person</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($suppliers as $supplier)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $supplier->name }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $supplier->contact_person ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $supplier->phone ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $supplier->email ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$supplier->is_active ? 'success' : 'slate'">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="text-slate-500 hover:text-primary-600">
                                            <x-icon name="pencil" class="size-4" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-slate-500 hover:text-danger-600">
                                                <x-icon name="trash" class="size-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $suppliers->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
