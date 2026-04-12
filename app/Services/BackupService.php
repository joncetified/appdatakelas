<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\InfrastructureReportItem;
use App\Models\Permission;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackupService
{
    public function create(): string
    {
        $timestamp = now()->format('Ymd_His_u');
        $directory = storage_path('app/backups');
        $filePath = $directory."/infrakelas_backup_{$timestamp}.json";

        File::ensureDirectoryExists($directory);

        $payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'app' => config('app.name'),
            ],
            'site_settings' => SiteSetting::query()->get()->toArray(),
            'permissions' => Permission::query()->get()->toArray(),
            'users' => User::withTrashed()->with('permissions:id,slug')->get()->toArray(),
            'classrooms' => Classroom::withTrashed()->get()->toArray(),
            'reports' => InfrastructureReport::withTrashed()->get()->toArray(),
            'report_items' => InfrastructureReportItem::query()->get()->toArray(),
            'income_entries' => Schema::hasTable('income_entries')
                ? IncomeEntry::withTrashed()->get()->toArray()
                : [],
        ];

        File::put($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }

    public function restore(UploadedFile $file): void
    {
        $payload = json_decode((string) $file->get(), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($payload): void {
            $tables = [
                'user_permissions',
                'activity_logs',
                'income_entries',
                'infrastructure_report_items',
                'infrastructure_reports',
                'classrooms',
                'users',
                'permissions',
                'site_settings',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (! empty($payload['permissions'])) {
                DB::table('permissions')->insert(array_map(
                    fn ($row) => collect($row)->except(['users'])->all(),
                    $payload['permissions']
                ));
            }

            if (! empty($payload['users'])) {
                $users = collect($payload['users'])->map(function ($row) {
                    $permissions = $row['permissions'] ?? [];
                    unset($row['permissions']);

                    return [
                        'user' => $row,
                        'permissions' => $permissions,
                    ];
                });

                DB::table('users')->insert($users->pluck('user')->all());

                $permissionIdsBySlug = Permission::query()->pluck('id', 'slug');
                $pivotRows = [];

                foreach ($users as $row) {
                    foreach ($row['permissions'] as $permission) {
                        $slug = $permission['slug'] ?? null;

                        if (! $slug || ! isset($permissionIdsBySlug[$slug])) {
                            continue;
                        }

                        $pivotRows[] = [
                            'user_id' => $row['user']['id'],
                            'permission_id' => $permissionIdsBySlug[$slug],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if ($pivotRows !== []) {
                    DB::table('user_permissions')->insert($pivotRows);
                }
            }

            if (! empty($payload['site_settings'])) {
                DB::table('site_settings')->insert($payload['site_settings']);
            }

            if (! empty($payload['classrooms'])) {
                DB::table('classrooms')->insert($payload['classrooms']);
            }

            if (! empty($payload['reports'])) {
                DB::table('infrastructure_reports')->insert($payload['reports']);
            }

            if (! empty($payload['report_items'])) {
                DB::table('infrastructure_report_items')->insert($payload['report_items']);
            }

            if (! empty($payload['income_entries']) && Schema::hasTable('income_entries')) {
                DB::table('income_entries')->insert($payload['income_entries']);
            }
        });
    }
}
