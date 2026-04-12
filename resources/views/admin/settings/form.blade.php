@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Pengaturan Website</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Pengaturan ini tersimpan di database dan dipakai untuk branding, kontak, captcha, dan Discord webhook.
        </p>
    </section>

    <section class="panel px-6 py-6 lg:px-8">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="company_name" class="label">Nama Perusahaan / Sekolah</label>
                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $settings->company_name) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="manager_name" class="label">Manager</label>
                    <input id="manager_name" name="manager_name" type="text" value="{{ old('manager_name', $settings->manager_name) }}" class="field mt-2">
                </div>

                <div>
                    <label for="contact_email" class="label">Email Kontak</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings->contact_email) }}" class="field mt-2">
                </div>

                <div>
                    <label for="contact_phone" class="label">Telepon Kontak</label>
                    <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone', $settings->contact_phone) }}" class="field mt-2">
                </div>

                <div>
                    <label for="contact_whatsapp" class="label">WhatsApp Kontak</label>
                    <input id="contact_whatsapp" name="contact_whatsapp" type="text" value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" class="field mt-2">
                </div>

                <div>
                    <label for="logo" class="label">Logo</label>
                    <input id="logo" name="logo" type="file" class="field mt-2">
                </div>
            </div>

            @if ($settings->logo_path)
                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-sm font-semibold text-slate-950">Logo Saat Ini</p>
                    <img src="{{ asset($settings->logo_path) }}" alt="Logo website" class="mt-4 h-20 w-auto max-w-[320px] object-contain">
                    <label class="mt-4 flex items-center gap-3 text-sm text-slate-600">
                        <input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 rounded border-slate-300">
                        Hapus logo saat ini
                    </label>
                </div>
            @endif

            <div>
                <label for="address" class="label">Alamat</label>
                <textarea id="address" name="address" rows="4" class="field mt-2">{{ old('address', $settings->address) }}</textarea>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="discord_webhook_url" class="label">Discord Webhook URL</label>
                    <input id="discord_webhook_url" name="discord_webhook_url" type="url" value="{{ old('discord_webhook_url', $settings->discord_webhook_url) }}" class="field mt-2">
                </div>

                <div>
                    <label for="google_recaptcha_site_key" class="label">Google reCAPTCHA Site Key</label>
                    <input id="google_recaptcha_site_key" name="google_recaptcha_site_key" type="text" value="{{ old('google_recaptcha_site_key', $settings->google_recaptcha_site_key) }}" class="field mt-2">
                </div>

                <div class="md:col-span-2">
                    <label for="google_recaptcha_secret_key" class="label">Google reCAPTCHA Secret Key</label>
                    <input id="google_recaptcha_secret_key" name="google_recaptcha_secret_key" type="text" value="{{ old('google_recaptcha_secret_key', $settings->google_recaptcha_secret_key) }}" class="field mt-2">
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </section>
@endsection
