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

        $defaultBrandName = 'SPH';
        $defaultBrandLogoPath = 'site/permata-harapan-logo.svg';
        $placeholderBrandNames = ['laravel', 'appdatakelas'];

        if (Schema::hasTable('permissions')) {
            Permission::syncDefaults();
        }

        if (Schema::hasTable('site_settings')) {
            $settings = SiteSetting::query()->first();

            if (! $settings) {
                $settings = SiteSetting::query()->create([
                    'company_name' => $defaultBrandName,
                    'logo_path' => $defaultBrandLogoPath,
                ]);
            }

            $rawBrandName = trim((string) $settings->company_name);
            $usesDefaultBrandName = $rawBrandName === ''
                || in_array(strtolower($rawBrandName), $placeholderBrandNames, true);
            $brandName = $usesDefaultBrandName ? $defaultBrandName : $rawBrandName;
            $brandLogoPath = blank($settings->logo_path) ? $defaultBrandLogoPath : $settings->logo_path;

            View::share('siteSettings', $settings);
            View::share('brandName', $brandName);
            View::share('brandLogoPath', $brandLogoPath);
        } else {
            View::share('siteSettings', null);
            View::share('brandName', $defaultBrandName);
            View::share('brandLogoPath', $defaultBrandLogoPath);
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
