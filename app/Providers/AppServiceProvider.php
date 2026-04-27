<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

    }
}
