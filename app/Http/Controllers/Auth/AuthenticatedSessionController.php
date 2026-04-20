<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
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
                ->withInput($request->only('email'))
                ->withErrors([
                    'initial_setup' => 'Belum ada akun yang bisa dipakai login. Buat super admin pertama terlebih dahulu.',
                ]);
        }

        $this->captchaService->validate($request);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        $request->session()->regenerate();

        if (! $request->user()?->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('success', 'Akun terdaftar, tetapi belum aktif. Verifikasi email terlebih dahulu.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
