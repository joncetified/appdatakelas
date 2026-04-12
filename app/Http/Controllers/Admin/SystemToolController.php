<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfrastructureReport;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class SystemToolController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly BackupService $backupService,
    ) {
    }

    public function index(): View
    {
        $backupDirectory = storage_path('app/backups');
        $files = File::exists($backupDirectory)
            ? collect(File::files($backupDirectory))->sortByDesc(fn ($file) => $file->getMTime())->values()
            : collect();

        return view('admin.tools.index', [
            'backupFiles' => $files,
        ]);
    }

    public function createBackup(): RedirectResponse
    {
        $path = $this->backupService->create();

        $this->activityService->log('backup.created', 'Backup database dibuat.', null, [
            'file' => basename($path),
        ]);

        return back()->with('success', 'Backup berhasil dibuat: '.basename($path));
    }

    public function downloadBackup(string $filename)
    {
        abort_if($filename !== basename($filename), 404);

        $path = storage_path('app/backups/'.$filename);
        abort_unless(File::exists($path), 404);

        return Response::download($path);
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Hanya super admin yang dapat merestore backup.');

        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json,txt'],
        ]);

        $this->backupService->restore($validated['backup_file']);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->activityService->log('backup.restored', 'Backup database direstore.');

        return redirect()->route('login')->with('success', 'Backup berhasil direstore. Silakan login kembali.');
    }

    public function clearCaches(): RedirectResponse
    {
        Artisan::call('optimize:clear');

        $this->activityService->log('system.cache_cleared', 'Cache aplikasi dibersihkan.');

        return back()->with('success', 'Cache aplikasi berhasil dibersihkan.');
    }

    public function resetDatabase(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Hanya super admin yang dapat me-reset database.');

        $validated = $request->validate([
            'confirmation' => ['required', 'in:RESET DATABASE'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Artisan::call('migrate:fresh', ['--force' => true]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole(User::ROLE_SUPER_ADMIN));

        Auth::login($user);
        $request->session()->regenerate();

        $this->activityService->log('system.database_reset', 'Database di-reset ulang dari aplikasi.', $user);

        return redirect()->route('dashboard')->with('success', 'Database berhasil di-reset dan super admin baru telah dibuat.');
    }

    public function exportUsers()
    {
        $rows = User::withTrashed()->with('permissions:id,slug')->get()->map(function (User $user): string {
            return implode(',', [
                $this->escapeCsv($user->name),
                $this->escapeCsv($user->email),
                $this->escapeCsv($user->role),
                $this->escapeCsv($user->whatsapp_number ?? ''),
                $this->escapeCsv($user->permissions->pluck('slug')->implode('|')),
                $this->escapeCsv($user->deleted_at?->toDateTimeString() ?? ''),
            ]);
        });

        $content = implode("\n", array_merge([
            'name,email,role,whatsapp_number,permissions,deleted_at',
        ], $rows->all()));

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export.csv"',
        ]);
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'users_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $allowedRoles = array_keys(User::manageableRoleOptionsFor($request->user()));
        $rows = $this->parseCsvRows($validated['users_file']);

        foreach ($rows as $data) {
            $role = trim((string) ($data['role'] ?? ''));
            if (! in_array($role, $allowedRoles, true)) {
                continue;
            }

            $email = trim((string) ($data['email'] ?? ''));
            if ($email === '') {
                continue;
            }

            $user = User::query()->firstOrNew(['email' => $email]);

            if ($user->exists && ! $user->canBeManagedBy($request->user())) {
                continue;
            }

            $user->fill([
                'name' => trim((string) ($data['name'] ?? '')),
                'role' => $role,
                'whatsapp_number' => trim((string) ($data['whatsapp_number'] ?? '')) ?: null,
            ]);

            if (! $user->exists) {
                $user->password = 'Password123!';
                $user->email_verified_at = now();
            } elseif (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();

            $permissions = collect(explode('|', (string) ($data['permissions'] ?? '')))
                ->map(fn ($slug) => trim($slug))
                ->filter()
                ->values()
                ->all();

            if (! $request->user()->isSuperAdmin()) {
                $permissions = [];
            }

            $user->syncPermissionsBySlugs($permissions !== [] ? $permissions : User::defaultPermissionSlugsForRole($user->role));
        }

        $this->activityService->log('users.imported', 'Import users dari CSV dijalankan.');

        return back()->with('success', 'Import users selesai diproses.');
    }

    public function exportItems()
    {
        $rows = InfrastructureReport::query()
            ->with('items')
            ->get()
            ->flatMap(fn ($report) => $report->items->map(function ($item) use ($report): string {
                return implode(',', [
                    $report->id,
                    $this->escapeCsv($item->item_name),
                    $item->total_units,
                    $item->damaged_units,
                    $this->escapeCsv($item->notes ?? ''),
                ]);
            }));

        $content = implode("\n", array_merge([
            'infrastructure_report_id,item_name,total_units,damaged_units,notes',
        ], $rows->all()));

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report_items_export.csv"',
        ]);
    }

    public function importItems(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        foreach ($this->parseCsvRows($validated['items_file']) as $data) {
            $report = InfrastructureReport::query()->find((int) ($data['infrastructure_report_id'] ?? 0));

            if (! $report) {
                continue;
            }

            $itemName = trim((string) ($data['item_name'] ?? ''));
            if ($itemName === '') {
                continue;
            }

            $report->items()->updateOrCreate([
                'item_name' => $itemName,
            ], [
                'item_name' => trim((string) ($data['item_name'] ?? '')),
                'total_units' => (int) ($data['total_units'] ?? 0),
                'damaged_units' => (int) ($data['damaged_units'] ?? 0),
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);
        }

        $this->activityService->log('items.imported', 'Import items laporan dari CSV dijalankan.');

        return back()->with('success', 'Import items selesai diproses.');
    }

    private function escapeCsv(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseCsvRows(\Illuminate\Http\UploadedFile $file): array
    {
        $contents = trim((string) $file->get());

        if ($contents === '') {
            return [];
        }

        $rows = array_map('str_getcsv', preg_split('/\r\n|\n|\r/', $contents));
        $header = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);

        if ($header === []) {
            return [];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '') ?? ($header[0] ?? '');
        $columnCount = count($header);
        $normalizedRows = [];

        foreach ($rows as $row) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $row = array_slice(array_pad($row, $columnCount, null), 0, $columnCount);
            $data = array_combine($header, $row);

            if ($data !== false) {
                $normalizedRows[] = $data;
            }
        }

        return $normalizedRows;
    }
}
