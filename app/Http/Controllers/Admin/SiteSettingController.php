<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {
    }

    public function edit(): View
    {
        return view('admin.settings.form', [
            'settings' => SiteSetting::query()->firstOrFail(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = SiteSetting::query()->firstOrFail();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'discord_webhook_url' => ['nullable', 'url'],
            'google_recaptcha_site_key' => ['nullable', 'string'],
            'google_recaptcha_secret_key' => ['nullable', 'string'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && $settings->logo_path) {
            File::delete(public_path($settings->logo_path));
            $validated['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                File::delete(public_path($settings->logo_path));
            }

            $extension = strtolower((string) $request->file('logo')->getClientOriginalExtension());
            $filename = Str::uuid()->toString().'.'.$extension;
            $directory = public_path('site');

            File::ensureDirectoryExists($directory);
            $request->file('logo')->move($directory, $filename);

            $validated['logo_path'] = 'site/'.$filename;
        }

        unset($validated['logo'], $validated['remove_logo']);

        $settings->update($validated);

        $this->activityService->log(
            action: 'settings.updated',
            description: 'Pengaturan website diperbarui.',
            subject: $settings,
        );

        return redirect()->route('admin.settings.edit')->with('success', 'Pengaturan website berhasil diperbarui.');
    }
}
