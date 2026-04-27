<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService,
    ) {}

    public function create(Request $request): View
    {
        return view('auth.register', [
            'captcha' => $this->captchaService->prepare($request),
            'registerRoles' => [
                User::ROLE_CLASS_LEADER => User::roleOptions()[User::ROLE_CLASS_LEADER],
                User::ROLE_HOMEROOM_TEACHER => User::roleOptions()[User::ROLE_HOMEROOM_TEACHER],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->captchaService->validate($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in([User::ROLE_CLASS_LEADER, User::ROLE_HOMEROOM_TEACHER])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'role' => $validated['role'],
            'password' => $validated['password'],
            'email_verified_at' => null,
        ]);

        $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole($user->role));

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('success', 'Registrasi berhasil. Cek email untuk aktivasi akun.');
    }
}
