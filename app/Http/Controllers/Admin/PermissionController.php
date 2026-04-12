<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Checklist hak akses hanya dapat diatur oleh super admin.');

        $search = trim($request->string('q')->toString());

        return view('admin.permissions.index', [
            'users' => User::query()
                ->with('permissions')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function edit(User $user): View
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403, 'Checklist hak akses hanya dapat diatur oleh super admin.');
        abort_if($user->isSuperAdmin(), 403, 'Permission super admin tidak diubah dari checklist.');

        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get()
            ->groupBy('group');

        return view('admin.permissions.form', [
            'user' => $user->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Checklist hak akses hanya dapat diatur oleh super admin.');
        abort_if($user->isSuperAdmin(), 403, 'Permission super admin tidak diubah dari checklist.');

        $allowedPermissionIds = Permission::query()->pluck('id')->all();
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'in:'.implode(',', $allowedPermissionIds)],
        ]);

        $user->permissions()->sync($validated['permissions'] ?? []);
        $user->unsetRelation('permissions');

        $this->activityService->log(
            action: 'permission.updated',
            description: "Checklist akses untuk {$user->email} diperbarui.",
            subject: $user,
            properties: ['permission_ids' => $validated['permissions'] ?? []],
        );

        return redirect()->route('admin.permissions.index')->with('success', 'Hak akses pengguna berhasil diperbarui.');
    }
}
