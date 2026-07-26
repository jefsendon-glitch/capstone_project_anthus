<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceLogRequest;
use App\Http\Requests\UpdateMaintenanceLogRequest;
use App\Models\MaintenanceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MaintenanceLog::class);

        $logs = MaintenanceLog::orderBy('next_due_date')->paginate(15);

        return view('admin.maintenance.index', compact('logs'));
    }

    public function create(): View
    {
        $this->authorize('create', MaintenanceLog::class);

        return view('admin.maintenance.create');
    }

    public function store(StoreMaintenanceLogRequest $request): RedirectResponse
    {
        MaintenanceLog::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.maintenance.index')->with('success', 'Maintenance log added successfully.');
    }

    public function edit(MaintenanceLog $maintenance): View
    {
        $this->authorize('update', $maintenance);

        return view('admin.maintenance.edit', ['log' => $maintenance]);
    }

    public function update(UpdateMaintenanceLogRequest $request, MaintenanceLog $maintenance): RedirectResponse
    {
        $maintenance->update($request->validated());

        return redirect()->route('admin.maintenance.index')->with('success', 'Maintenance log updated successfully.');
    }

    public function destroy(MaintenanceLog $maintenance): RedirectResponse
    {
        $this->authorize('delete', $maintenance);

        $maintenance->delete();

        return redirect()->route('admin.maintenance.index')->with('success', 'Maintenance log deleted successfully.');
    }
}
