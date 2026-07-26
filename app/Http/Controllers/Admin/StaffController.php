<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $teamQuery = User::role(['admin', 'staff']);

        $teamStats = [
            'total' => (clone $teamQuery)->count(),
            'admins' => (clone $teamQuery)->role('admin')->count(),
            'active' => (clone $teamQuery)->where('status', 'active')->count(),
            'staff' => (clone $teamQuery)->role('staff')->count(),
        ];

        $team = $teamQuery
            ->with(['staff', 'roles'])
            ->withCount('processedSalesTransactions')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy('name')
            ->paginate(15);

        return view('admin.staff.index', compact('team', 'teamStats'));
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'] ?? null,
                'status' => $validated['status'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['role']);

            if ($validated['role'] === 'staff') {
                Staff::create([
                    'user_id' => $user->id,
                    'employee_id' => $validated['employee_id'],
                    'position' => $validated['position'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.staff.index')->with('success', 'Account created successfully.');
    }

    public function edit(User $staff): View
    {
        $this->ensureTeamMember($staff);
        $staff->load('staff');

        return view('admin.staff.edit', ['member' => $staff]);
    }

    public function update(UpdateTeamMemberRequest $request, User $staff): RedirectResponse
    {
        $this->ensureTeamMember($staff);
        $validated = $request->validated();

        if ($staff->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove your own administrator access.']);
        }

        if ($staff->hasRole('admin') && $validated['role'] !== 'admin' && User::role('admin')->count() <= 1) {
            return back()->withErrors(['role' => 'At least one administrator account must remain.']);
        }

        DB::transaction(function () use ($validated, $staff) {
            $staff->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'] ?? null,
                'status' => $validated['status'],
                ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
            ]);

            $staff->syncRoles([$validated['role']]);

            if ($validated['role'] === 'staff') {
                Staff::updateOrCreate(
                    ['user_id' => $staff->id],
                    [
                        'employee_id' => $validated['employee_id'],
                        'position' => $validated['position'] ?? null,
                        'hire_date' => $validated['hire_date'] ?? null,
                    ]
                );
            } else {
                $staff->staff()->delete();
            }
        });

        return redirect()->route('admin.staff.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->ensureTeamMember($staff);

        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($staff->hasRole('admin') && User::role('admin')->count() <= 1) {
            return back()->with('error', 'At least one administrator account must remain.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Account deleted successfully.');
    }

    private function ensureTeamMember(User $member): void
    {
        abort_unless($member->hasAnyRole(['admin', 'staff']), 404);
    }
}
