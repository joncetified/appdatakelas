<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Checklist hak akses hanya dapat diatur oleh super admin.');

        $search = trim($request->string('q')->toString());
        $permissionCounts = Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->selectRaw('role_permissions.role, count(*) as total')
            ->groupBy('role_permissions.role')
            ->pluck('total', 'role')
            ->map(fn (mixed $total): int => (int) $total);
        $userCounts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->map(fn (mixed $total): int => (int) $total);

        return view('admin.permissions.index', [
            'roles' => collect(User::roleOptions())
                ->map(fn (string $label, string $role): array => [
                    'role' => $role,
                    'label' => $label,
                    'permission_count' => $role === User::ROLE_SUPER_ADMIN
                        ? Permission::query()->count()
                        : ($permissionCounts[$role] ?? 0),
                    'user_count' => $userCounts[$role] ?? 0,
                ])
                ->when($search !== '', fn (Collection $roles): Collection => $roles->filter(
                    fn (array $role): bool => str_contains(strtolower($role['label']), strtolower($search))
                        || str_contains(strtolower($role['role']), strtolower($search)),
                ))
                ->values(),
        ]);
    }

    public function edit(string $role): View
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403, 'Checklist hak akses hanya dapat diatur oleh super admin.');
        abort_unless(array_key_exists($role, User::roleOptions()), 404);
        abort_if($role === User::ROLE_SUPER_ADMIN, 403, 'Permission super admin tidak diubah dari checklist.');

        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get()
            ->groupBy('group');
        $selectedPermissionIds = Permission::query()
            ->whereIn('slug', User::permissionSlugsForRole($role))
            ->pluck('id')
            ->all();

        return view('admin.permissions.form', [
            'role' => $role,
            'roleLabel' => User::roleOptions()[$role],
            'selectedPermissionIds' => $selectedPermissionIds,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Checklist hak akses hanya dapat diatur oleh super admin.');
        abort_unless(array_key_exists($role, User::roleOptions()), 404);
        abort_if($role === User::ROLE_SUPER_ADMIN, 403, 'Permission super admin tidak diubah dari checklist.');

        $allowedPermissionIds = Permission::query()->pluck('id')->all();
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'in:'.implode(',', $allowedPermissionIds)],
        ]);

        User::syncRolePermissionIds($role, $validated['permissions'] ?? []);

        $this->activityService->log(
            action: 'permission.updated',
            description: 'Checklist akses role '.User::roleOptions()[$role].' diperbarui.',
            properties: [
                'role' => $role,
                'permission_ids' => $validated['permissions'] ?? [],
            ],
        );

        return redirect()->route('admin.permissions.index')->with('success', 'Hak akses role berhasil diperbarui.');
    }
}
