<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService,
    ) {
    }

    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'captcha' => $this->captchaService->prepare($request),
            'supportWhatsapp' => $this->supportWhatsapp(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->captchaService->validate($request);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'channel' => ['required', 'in:email,whatsapp'],
        ]);

        if ($validated['channel'] === 'email') {
            $status = Password::sendResetLink([
                'email' => $validated['email'],
            ]);

            return back()->with('success', __($status));
        }

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Email tidak ditemukan.',
            ])->withInput();
        }

        $supportWhatsapp = $this->supportWhatsapp();

        if (blank($supportWhatsapp)) {
            return back()->withErrors([
                'channel' => 'Nomor WhatsApp support belum diatur pada pengaturan website.',
            ])->withInput();
        }

        $message = rawurlencode("Halo, saya ingin reset password untuk akun {$user->email}. Mohon bantu reset akun saya.");
        $link = 'https://wa.me/'.preg_replace('/\D+/', '', $supportWhatsapp).'?text='.$message;

        return redirect()->away($link);
    }

    private function supportWhatsapp(): ?string
    {
        if (! Schema::hasTable('site_settings')) {
            return null;
        }

        return SiteSetting::query()->value('contact_whatsapp');
    }
}
