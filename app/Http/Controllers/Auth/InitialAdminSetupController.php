<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\InputRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InitialAdminSetupController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        abort_unless(config('app.setup_allowed'), 403, 'Akses ke halaman setup admin telah dinonaktifkan.');

        if (User::query()->exists()) {
            return redirect()->route($request->user() ? 'dashboard' : 'login');
        }

        return view('auth.setup-admin');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('app.setup_allowed'), 403, 'Akses ke halaman setup admin telah dinonaktifkan.');

        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => InputRules::humanName(),
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_number' => InputRules::phone(minDigits: 10),
            'password' => InputRules::password(),
        ]);

        $admin = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $admin->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole(User::ROLE_SUPER_ADMIN));

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Super admin pertama berhasil dibuat.');
    }
}
