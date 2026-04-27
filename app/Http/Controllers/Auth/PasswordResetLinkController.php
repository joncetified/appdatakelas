<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Services\CaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    private const OTP_VALID_MINUTES = 10;

    private const OTP_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly CaptchaService $captchaService,
    ) {}

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
        $request->merge([
            'login' => $request->input('login', $request->input('email')),
        ]);

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:email,whatsapp'],
        ]);

        $user = $this->findUserForReset($validated['login']);

        if (! $user) {
            return back()->withErrors([
                'login' => 'Username atau email tidak ditemukan.',
            ])->withInput();
        }

        if ($validated['channel'] === 'email') {
            $this->issuePasswordResetOtp($request, $user);

            return redirect()
                ->route('password.otp')
                ->with('success', 'Kode OTP reset password sudah dikirim ke email akun Anda.');
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

    public function createOtp(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingPasswordResetOtp($request);

        if (! $pending) {
            return redirect()
                ->route('password.request')
                ->withErrors(['otp' => 'Sesi OTP reset password tidak ditemukan. Minta kode baru terlebih dahulu.']);
        }

        return view('auth.forgot-password-otp', [
            'email' => $pending['email'],
            'expiresAt' => $pending['expires_at'],
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = $this->pendingPasswordResetOtp($request);

        if (! $pending) {
            return redirect()
                ->route('password.request')
                ->withErrors(['otp' => 'Sesi OTP reset password tidak ditemukan atau sudah kedaluwarsa. Minta kode baru terlebih dahulu.']);
        }

        if (! hash_equals($pending['otp_hash'], hash('sha256', $validated['otp']))) {
            $attempts = $pending['attempts'] + 1;

            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                $this->clearPendingPasswordResetOtp($request);

                return redirect()
                    ->route('password.request')
                    ->withErrors(['otp' => 'Kode OTP salah terlalu sering. Minta kode baru terlebih dahulu.']);
            }

            $request->session()->put('password_reset_otp.attempts', $attempts);

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid.',
            ]);
        }

        $user = User::query()->find($pending['user_id']);

        if (! $user) {
            $this->clearPendingPasswordResetOtp($request);

            return redirect()
                ->route('password.request')
                ->withErrors(['login' => 'Akun tidak ditemukan.']);
        }

        $this->clearPendingPasswordResetOtp($request);
        $token = Password::broker()->createToken($user);

        return redirect()
            ->route('password.reset', ['token' => $token, 'email' => $user->email])
            ->with('success', 'OTP benar. Silakan buat password baru.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $this->pendingPasswordResetOtp($request);

        if (! $pending) {
            return redirect()
                ->route('password.request')
                ->withErrors(['otp' => 'Sesi OTP reset password tidak ditemukan atau sudah kedaluwarsa. Minta kode baru terlebih dahulu.']);
        }

        $user = User::query()->find($pending['user_id']);

        if (! $user) {
            $this->clearPendingPasswordResetOtp($request);

            return redirect()
                ->route('password.request')
                ->withErrors(['login' => 'Akun tidak ditemukan.']);
        }

        $this->issuePasswordResetOtp($request, $user);

        return redirect()
            ->route('password.otp')
            ->with('success', 'Kode OTP baru sudah dikirim ke email akun Anda.');
    }

    private function supportWhatsapp(): ?string
    {
        if (! Schema::hasTable('site_settings')) {
            return null;
        }

        return SiteSetting::query()->value('contact_whatsapp');
    }

    private function findUserForReset(string $login): ?User
    {
        $login = trim($login);

        $user = User::query()->where('email', $login)->first();

        if ($user) {
            return $user;
        }

        $matches = User::query()->where('name', $login)->get();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'login' => 'Username ini terdaftar pada lebih dari satu akun. Masukkan email akun atau hubungi admin.',
            ]);
        }

        return $matches->first();
    }

    private function issuePasswordResetOtp(Request $request, User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_VALID_MINUTES);

        $request->session()->put('password_reset_otp', [
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => $expiresAt->timestamp,
        ]);

        $user->notify(new PasswordResetOtpNotification($code, self::OTP_VALID_MINUTES));
    }

    /**
     * @return array{user_id: int, email: string, otp_hash: string, attempts: int, expires_at: int}|null
     */
    private function pendingPasswordResetOtp(Request $request): ?array
    {
        $pending = $request->session()->get('password_reset_otp');

        if (! is_array($pending)) {
            return null;
        }

        $expiresAt = (int) ($pending['expires_at'] ?? 0);

        if ($expiresAt <= now()->timestamp) {
            $this->clearPendingPasswordResetOtp($request);

            return null;
        }

        return [
            'user_id' => (int) $pending['user_id'],
            'email' => (string) $pending['email'],
            'otp_hash' => (string) $pending['otp_hash'],
            'attempts' => (int) $pending['attempts'],
            'expires_at' => $expiresAt,
        ];
    }

    private function clearPendingPasswordResetOtp(Request $request): void
    {
        $request->session()->forget('password_reset_otp');
    }
}
