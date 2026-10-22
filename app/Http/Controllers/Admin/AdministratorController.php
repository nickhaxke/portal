<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    public function index(): View
    {
        $admins = User::whereIn('role', ['superadmin', 'admin', 'executive', 'editor', 'viewer'])
            ->orderByRaw("FIELD(role, 'superadmin', 'admin', 'executive', 'editor', 'viewer')")
            ->get();

        return view('admin.administrators.index', compact('admins'));
    }

    public function create(): View
    {
        $roles = [
            'superadmin' => 'Super Administrator (Full System Authority)',
            'admin' => 'Library Manager (Curatorial & Catalog Control)',
            'editor' => 'Content Editor (Insights & Metadata)',
            'viewer' => 'Auditor / Viewer (Read-only Admin)',
        ];

        return view('admin.administrators.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:superadmin,admin,editor,viewer'],
            'position_title' => ['nullable', 'string', 'max:100'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'position_title' => $validated['position_title'] ?? 'Administrator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        AuditLogService::log(
            'admin.create',
            $admin,
            null,
            ['id' => $admin->id, 'email' => $admin->email, 'role' => $admin->role]
        );

        return redirect()->route('admin.administrators.index')
            ->with('success', "Administrator account for '{$admin->name}' created successfully.");
    }

    public function edit(User $administrator): View
    {
        $roles = [
            'superadmin' => 'Super Administrator (Full System Authority)',
            'admin' => 'Library Manager (Curatorial & Catalog Control)',
            'editor' => 'Content Editor (Insights & Metadata)',
            'viewer' => 'Auditor / Viewer (Read-only Admin)',
        ];

        return view('admin.administrators.edit', compact('administrator', 'roles'));
    }

    public function update(Request $request, User $administrator): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email,'.$administrator->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:superadmin,admin,editor,viewer'],
            'position_title' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        // Safety rule: Cannot demote or suspend self
        if ($administrator->id === auth()->id()) {
            if ($validated['status'] === 'suspended') {
                return back()->withErrors(['status' => 'You cannot suspend your own administrator account.']);
            }
            if ($validated['role'] !== auth()->user()->role && auth()->user()->role === 'superadmin') {
                return back()->withErrors(['role' => 'You cannot demote your own Superadmin account.']);
            }
        }

        $oldValues = [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'role' => $administrator->role,
            'status' => $administrator->status,
        ];

        $administrator->name = $validated['name'];
        $administrator->email = $validated['email'];
        $administrator->role = $validated['role'];
        $administrator->position_title = $validated['position_title'] ?? $administrator->position_title;
        $administrator->status = $validated['status'];

        if (! empty($validated['password'])) {
            $administrator->password = Hash::make($validated['password']);
        }

        $administrator->save();

        AuditLogService::log(
            'admin.update',
            $administrator,
            $oldValues,
            [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'role' => $administrator->role,
                'status' => $administrator->status,
            ]
        );

        return redirect()->route('admin.administrators.index')
            ->with('success', "Administrator account for '{$administrator->name}' updated.");
    }

    public function destroy(User $administrator): RedirectResponse
    {
        if ($administrator->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Must leave at least one superadmin
        if ($administrator->role === 'superadmin' && User::where('role', 'superadmin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the sole Super Administrator account.');
        }

        $oldValues = ['id' => $administrator->id, 'email' => $administrator->email, 'name' => $administrator->name];
        $name = $administrator->name;
        $administrator->delete();

        AuditLogService::log('admin.delete', null, $oldValues, null);

        return redirect()->route('admin.administrators.index')
            ->with('success', "Administrator '{$name}' removed.");
    }
}
