<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityService;
use App\Support\InputRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $roleSearch = str_replace(' ', '_', strtolower($search));
        $matchingRoles = collect(User::roleOptions())
            ->filter(fn (string $label, string $role): bool => str_contains(strtolower($label), strtolower($search))
                || str_contains(strtolower(str_replace('_', ' ', $role)), strtolower($search))
                || str_contains($role, $roleSearch))
            ->keys()
            ->all();
        $activeUserIds = $this->activeSessionUserIds();

        if ($request->user()) {
            $activeUserIds[] = $request->user()->id;
            $activeUserIds = array_values(array_unique($activeUserIds));
        }

        return view('admin.users.index', [
            'users' => User::query()
                ->with(['ledClassroom', 'homeroomClassrooms', 'createdByUser', 'updatedByUser'])
                ->when($search !== '', function ($query) use ($search, $matchingRoles): void {
                    $query->where(function ($builder) use ($search, $matchingRoles): void {
                        $builder
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('role', 'like', "%{$search}%");

                        if ($matchingRoles !== []) {
                            $builder->orWhereIn('role', $matchingRoles);
                        }
                    });
                })
                ->orderBy('role')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'activeUserIds' => $activeUserIds,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', [
            'user' => new User,
            'roleOptions' => User::manageableRoleOptionsFor($request->user()),
            'pageTitle' => 'Tambah Pengguna',
            'submitLabel' => 'Simpan Pengguna',
            'action' => route('admin.users.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request, $request->user());

        $user = User::query()->create([
            ...$validated,
            'email_verified_at' => User::roleRequiresEmailVerification($validated['role']) ? null : now(),
        ]);
        $user->syncPermissionsBySlugs(User::permissionSlugsForRole($user->role));

        $this->activityService->log(
            action: 'user.created',
            description: "User {$user->email} ditambahkan.",
            subject: $user,
            properties: ['role' => $user->role],
        );

        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($user->canBeManagedBy($request->user()), 403);

        return view('admin.users.form', [
            'user' => $user,
            'roleOptions' => $this->roleOptionsForForm($request->user(), $user),
            'pageTitle' => 'Edit Pengguna',
            'submitLabel' => 'Perbarui Pengguna',
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->canBeManagedBy($request->user()), 403);

        $validated = $this->validatePayload($request, $request->user(), $user);
        $targetRole = $validated['role'] ?? $user->role;

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        if (
            $user->isSuperAdmin()
            && ($validated['role'] ?? $user->role) !== User::ROLE_SUPER_ADMIN
            && User::query()->where('role', User::ROLE_SUPER_ADMIN)->count() === 1
        ) {
            throw ValidationException::withMessages([
                'role' => 'Minimal harus ada satu super admin dalam sistem.',
            ]);
        }

        $roleChanged = $targetRole !== $user->role;
        $emailChanged = ($validated['email'] ?? $user->email) !== $user->email;

        if ($roleChanged || $emailChanged) {
            $validated['email_verified_at'] = User::roleRequiresEmailVerification($targetRole)
                ? null
                : ($user->email_verified_at ?? now());
        }

        $user->update($validated);

        if ($roleChanged) {
            $user->syncPermissionsBySlugs(User::permissionSlugsForRole($user->role));
        }

        $this->activityService->log(
            action: 'user.updated',
            description: "User {$user->email} diperbarui.",
            subject: $user,
            properties: ['role' => $user->role],
        );

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->canBeManagedBy($request->user()), 403);
        abort_if($request->user()->is($user), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        abort_if(
            $user->isSuperAdmin() && User::query()->where('role', User::ROLE_SUPER_ADMIN)->count() === 1,
            422,
            'Minimal harus ada satu super admin dalam sistem.',
        );

        $user->delete();

        $this->activityService->log(
            action: 'user.deleted',
            description: "User {$user->email} dihapus.",
            subject: $user,
        );

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, User $actor, ?User $user = null): array
    {
        $allowedRoles = array_keys(User::manageableRoleOptionsFor($actor));

        if ($user?->isSuperAdmin()) {
            $allowedRoles[] = User::ROLE_SUPER_ADMIN;
            $allowedRoles = array_values(array_unique($allowedRoles));
        }

        abort_if($allowedRoles === [], 403);

        $passwordRules = $user
            ? InputRules::password(false)
            : InputRules::password();

        return $request->validate([
            'name' => InputRules::humanName(),
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'whatsapp_number' => InputRules::phone(minDigits: 10),
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => $passwordRules,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function roleOptionsForForm(User $actor, ?User $user = null): array
    {
        $options = User::manageableRoleOptionsFor($actor);

        if ($user?->isSuperAdmin()) {
            return [User::ROLE_SUPER_ADMIN => User::roleOptions()[User::ROLE_SUPER_ADMIN]] + $options;
        }

        return $options;
    }

    /**
     * @return list<int>
     */
    private function activeSessionUserIds(): array
    {
        $sessionTable = (string) config('session.table', 'sessions');

        if (! Schema::hasTable($sessionTable)) {
            return [];
        }

        $threshold = now()
            ->subMinutes((int) config('session.lifetime', 120))
            ->timestamp;

        return DB::table($sessionTable)
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $threshold)
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
