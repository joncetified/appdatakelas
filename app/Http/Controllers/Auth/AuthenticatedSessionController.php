<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Services\CaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    private const OTP_VALID_MINUTES = 10;

    private const OTP_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly CaptchaService $captchaService,
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $needsInitialSetup = ! User::query()->exists();

        return view('auth.login', [
            'captcha' => $this->captchaService->prepare($request),
            'needsInitialSetup' => $needsInitialSetup,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! User::query()->exists()) {
            return redirect()
                ->route('login')
                ->withInput($request->only('login'))
                ->withErrors([
                    'initial_setup' => 'Belum ada akun yang bisa dipakai login. Buat super admin pertama terlebih dahulu.',
                ]);
        }

        $this->captchaService->validate($request);
        $request->merge([
            'login' => $request->input('login', $request->input('email')),
        ]);

        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) $credentials['login']);
        $user = User::query()
            ->where('name', $login)
            ->orWhere('email', $login)
            ->get()
            ->first(fn (User $candidate): bool => Hash::check($credentials['password'], $candidate->password));

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => 'Username atau password tidak valid.',
            ]);
        }

        $this->issueLoginOtp($request, $user, $request->boolean('remember'));

        return redirect()
            ->route('login.otp')
            ->with('success', 'Password valid. Kode OTP sudah dikirim ke email akun Anda.');
    }

    public function createOtp(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingOtp($request);

        if (! $pending) {
            return redirect()
                ->route('login')
                ->withErrors(['otp' => 'Sesi OTP tidak ditemukan. Login ulang untuk meminta kode baru.']);
        }

        return view('auth.login-otp', [
            'email' => $pending['email'],
            'expiresAt' => $pending['expires_at'],
        ]);
    }

    public function googleRedirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! $this->googleLoginConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URL.',
                ]);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        if (! $this->googleLoginConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Login Google belum dikonfigurasi.',
                ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Login Google gagal atau dibatalkan. Coba lagi.',
                ]);
        }

        $googleId = $googleUser->getId();
        $email = Str::lower((string) $googleUser->getEmail());

        if (blank($googleId) || blank($email)) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Akun Google tidak mengirim data identitas yang lengkap.',
                ]);
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $isFirstUser = ! User::query()->exists();
            $role = $isFirstUser ? User::ROLE_SUPER_ADMIN : User::ROLE_CLASS_LEADER;

            $user = User::query()->create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => $this->googleEmailVerified($googleUser) ? now() : null,
                'password' => Hash::make(Str::random(48)),
                'role' => $role,
            ]);

            $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole($role));
        }

        $updates = [
            'google_id' => $googleId,
        ];

        if ($this->googleEmailVerified($googleUser) && $user->requiresEmailVerification() && ! $user->hasVerifiedEmail()) {
            $updates['email_verified_at'] = now();
        }

        $user->forceFill($updates)->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        if (! $request->user()?->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('success', 'Login Google berhasil, tetapi akun belum aktif. Verifikasi email terlebih dahulu.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = $this->pendingOtp($request);

        if (! $pending) {
            return redirect()
                ->route('login')
                ->withErrors(['otp' => 'Sesi OTP tidak ditemukan atau sudah kedaluwarsa. Login ulang untuk meminta kode baru.']);
        }

        if (! hash_equals($pending['otp_hash'], hash('sha256', $validated['otp']))) {
            $attempts = $pending['attempts'] + 1;

            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                $this->clearPendingOtp($request);

                return redirect()
                    ->route('login')
                    ->withErrors(['otp' => 'Kode OTP salah terlalu sering. Login ulang untuk meminta kode baru.']);
            }

            $request->session()->put('login_otp.attempts', $attempts);

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid.',
            ]);
        }

        $user = User::query()->find($pending['user_id']);

        if (! $user) {
            $this->clearPendingOtp($request);

            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Akun tidak ditemukan.']);
        }

        $remember = $pending['remember'];
        $this->clearPendingOtp($request);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if (! $request->user()?->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('success', 'Akun terdaftar, tetapi belum aktif. Verifikasi email terlebih dahulu.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $this->pendingOtp($request);

        if (! $pending) {
            return redirect()
                ->route('login')
                ->withErrors(['otp' => 'Sesi OTP tidak ditemukan atau sudah kedaluwarsa. Login ulang untuk meminta kode baru.']);
        }

        $user = User::query()->find($pending['user_id']);

        if (! $user) {
            $this->clearPendingOtp($request);

            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Akun tidak ditemukan.']);
        }

        $this->issueLoginOtp($request, $user, $pending['remember']);

        return redirect()
            ->route('login.otp')
            ->with('success', 'Kode OTP baru sudah dikirim ke email akun Anda.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function issueLoginOtp(Request $request, User $user, bool $remember): void
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_VALID_MINUTES);

        $request->session()->put('login_otp', [
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => hash('sha256', $code),
            'remember' => $remember,
            'attempts' => 0,
            'expires_at' => $expiresAt->timestamp,
        ]);

        $user->notify(new LoginOtpNotification($code, self::OTP_VALID_MINUTES));
    }

    /**
     * @return array{user_id: int, email: string, otp_hash: string, remember: bool, attempts: int, expires_at: int}|null
     */
    private function pendingOtp(Request $request): ?array
    {
        $pending = $request->session()->get('login_otp');

        if (! is_array($pending)) {
            return null;
        }

        $expiresAt = (int) ($pending['expires_at'] ?? 0);

        if ($expiresAt <= now()->timestamp) {
            $this->clearPendingOtp($request);

            return null;
        }

        return [
            'user_id' => (int) $pending['user_id'],
            'email' => (string) $pending['email'],
            'otp_hash' => (string) $pending['otp_hash'],
            'remember' => (bool) $pending['remember'],
            'attempts' => (int) $pending['attempts'],
            'expires_at' => $expiresAt,
        ];
    }

    private function clearPendingOtp(Request $request): void
    {
        $request->session()->forget('login_otp');
    }

    private function googleLoginConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function googleEmailVerified(mixed $googleUser): bool
    {
        $rawUser = is_array($googleUser->user ?? null) ? $googleUser->user : [];

        return ($rawUser['email_verified'] ?? false) === true;
    }
}
