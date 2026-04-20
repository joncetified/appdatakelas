<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    private const DEFAULT_LOGO_PATH = 'site/permata-harapan-logo.svg';

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
            $this->deleteCustomLogo($settings->logo_path);
            $validated['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $this->storeLogo($request->file('logo'));

            if ($settings->logo_path && $settings->logo_path !== $validated['logo_path']) {
                $this->deleteCustomLogo($settings->logo_path);
            }
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

    private function storeLogo(UploadedFile $logo): string
    {
        $extension = strtolower((string) $logo->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'.'.$extension;

        Storage::build([
            'driver' => 'local',
            'root' => public_path('site'),
            'throw' => true,
        ])->putFileAs('', $logo, $filename);

        return 'site/'.$filename;
    }

    private function deleteCustomLogo(?string $logoPath): void
    {
        if (blank($logoPath) || $logoPath === self::DEFAULT_LOGO_PATH) {
            return;
        }

        File::delete(public_path($logoPath));
    }
}
