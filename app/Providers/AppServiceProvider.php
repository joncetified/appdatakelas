<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        \Illuminate\Support\Facades\Validator::extend('no_long_words', function ($attribute, $value, $parameters, $validator) {
            if (! is_string($value)) {
                return true;
            }
            $maxLength = ! empty($parameters) ? (int) $parameters[0] : 30;
            return ! preg_match('/\S{' . ($maxLength + 1) . ',}/u', $value);
        }, 'Kolom :attribute tidak boleh mengandung kata yang lebih dari :max_word_len karakter.');

        \Illuminate\Support\Facades\Validator::replacer('no_long_words', function ($message, $attribute, $rule, $parameters) {
            $maxLength = ! empty($parameters) ? (int) $parameters[0] : 30;
            return str_replace(':max_word_len', $maxLength, $message);
        });

        Carbon::setLocale(config('app.locale'));
        $sessionDomain = config('session.domain');

        if ($sessionDomain === 'null' || $sessionDomain === '') {
            config(['session.domain' => null]);
        }

        config([
            'session.secure' => true,
            'session.same_site' => 'lax',
            'session.cookie' => config('session.cookie') ?: 'jonathan_session',
        ]);

        $defaultBrandName = 'Sekolah Permata Harapan';
        $defaultBrandLogoPath = 'site/logo ph.png';
        $placeholderBrandNames = ['laravel', 'appdatakelas', 'sekolah'];

        try {
            $hasPermissionsTable = Schema::hasTable('permissions');
            $hasSiteSettingsTable = Schema::hasTable('site_settings');
        } catch (Throwable) {
            View::share('siteSettings', null);
            View::share('brandName', $defaultBrandName);
            View::share('brandLogoPath', $defaultBrandLogoPath);

            return;
        }

        if ($hasPermissionsTable) {
            Permission::syncDefaults();
        }

        if ($hasSiteSettingsTable) {
            $settings = SiteSetting::query()->first();

            if (! $settings) {
                $settings = SiteSetting::query()->create([
                    'company_name' => $defaultBrandName,
                    'logo_path' => $defaultBrandLogoPath,
                ]);
            }

            $rawBrandName = trim((string) $settings->company_name);
            $usesDefaultBrandName = $rawBrandName === ''
                || mb_strlen($rawBrandName) > 40
                || preg_match('/\S{25,}/u', $rawBrandName)
                || $rawBrandName !== strip_tags($rawBrandName)
                || str_contains($rawBrandName, '<')
                || str_contains($rawBrandName, '>')
                || str_contains($rawBrandName, '=')
                || in_array(strtolower($rawBrandName), $placeholderBrandNames, true);
            $brandName = $usesDefaultBrandName ? $defaultBrandName : $rawBrandName;
            $rawLogoPath = trim((string) $settings->logo_path);
            $brandLogoPath = $rawLogoPath !== '' ? $rawLogoPath : $defaultBrandLogoPath;

            if (
                str_contains($brandLogoPath, '..')
                || str_starts_with($brandLogoPath, '/')
                || ! is_file(public_path($brandLogoPath))
            ) {
                $brandLogoPath = $defaultBrandLogoPath;
            }

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
