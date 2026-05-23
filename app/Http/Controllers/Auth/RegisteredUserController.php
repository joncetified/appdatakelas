<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\InputRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => InputRules::humanName(),
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_number' => InputRules::phone(minDigits: 10),
            'password' => InputRules::password(),
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'role' => User::ROLE_CLASS_LEADER,
            'email_verified_at' => null,
        ]);

        $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole(User::ROLE_CLASS_LEADER));

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice')->with('success', 'Akun berhasil dibuat. Link verifikasi sudah dikirim ke email Anda.');
    }
}
