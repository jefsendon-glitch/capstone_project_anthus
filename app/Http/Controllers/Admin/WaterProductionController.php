<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaterProductionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WaterProductionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WaterProductionLog::class);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $request->validate(['date_to' => ['after_or_equal:date_from']]);
        }
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $query = WaterProductionLog::with('producedBy')->whereNotNull('container_name')
            ->when($dateFrom, fn ($query) => $query->whereDate('production_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('production_date', '<=', $dateTo))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($query) use ($search) {
                    $query->where('container_name', 'like', $search)->orWhere('container_size', 'like', $search);
                });
            });
        $summary = [
            'batches' => (clone $query)->count(),
            'filledContainers' => (clone $query)->whereNotNull('container_name')->sum('gallons_produced'),
        ];
        $logs = $query->latest('production_date')->latest('id')->paginate(15)->withQueryString();

        return view('admin.water-production.index', compact('logs', 'dateFrom', 'dateTo', 'filters', 'summary'));
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate([
            'reset_confirmation' => ['required', 'in:RESET'],
        ]);

        DB::transaction(function () {
            WaterProductionLog::query()->whereNotNull('container_name')->delete();
        });

        return redirect()->route('admin.water-production.index')
            ->with('success', 'Gallon production records reset successfully. Gallon stock and movement history preserved.');
    }

}
