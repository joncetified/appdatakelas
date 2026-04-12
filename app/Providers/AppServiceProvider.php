<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        if (Schema::hasTable('permissions')) {
            Permission::syncDefaults();
        }

        if (Schema::hasTable('site_settings')) {
            $settings = SiteSetting::query()->first();

            if (! $settings) {
                $settings = SiteSetting::query()->create([
                    'company_name' => config('app.name', 'InfraKelas'),
                ]);
            }

            View::share('siteSettings', $settings);
        } else {
            View::share('siteSettings', null);
        }

        if (
            Schema::hasTable('permissions')
            && Schema::hasTable('user_permissions')
            && Schema::hasTable('users')
            && Schema::hasColumn('users', 'role')
            && Schema::hasColumn('users', 'deleted_at')
        ) {
            User::query()->withCount('permissions')->get()->each(function (User $user): void {
                if ($user->permissions_count === 0) {
                    $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole($user->role));
                }
            });
        }
    }
}
