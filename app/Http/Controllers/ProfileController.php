<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        if ($emailChanged) {
            $validated['email_verified_at'] = User::roleRequiresEmailVerification($user->role)
                ? null
                : ($user->email_verified_at ?? now());
        }

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            $this->deleteAvatar($user->avatar_path);
            $validated['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $this->storeAvatar($request->file('avatar'));

            if ($user->avatar_path && $user->avatar_path !== $validated['avatar_path']) {
                $this->deleteAvatar($user->avatar_path);
            }
        }

        unset($validated['avatar'], $validated['remove_avatar']);

        $user->update($validated);

        $this->activityService->log(
            action: 'profile.updated',
            description: "Profil akun {$user->email} diperbarui oleh pemilik akun.",
            subject: $user,
        );

        if ($emailChanged && User::roleRequiresEmailVerification($user->role)) {
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('verification.notice')
                ->with('success', 'Profil diperbarui. Email baru perlu diverifikasi lagi.');
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profil akun berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $this->activityService->log(
            action: 'profile.password_updated',
            description: "Password akun {$user->email} diperbarui oleh pemilik akun.",
            subject: $user,
        );

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Password akun berhasil diperbarui.');
    }

    private function storeAvatar(UploadedFile $avatar): string
    {
        $extension = strtolower((string) $avatar->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'.'.$extension;

        Storage::build([
            'driver' => 'local',
            'root' => public_path('avatars'),
            'throw' => true,
        ])->putFileAs('', $avatar, $filename);

        return 'avatars/'.$filename;
    }

    private function deleteAvatar(?string $avatarPath): void
    {
        if (blank($avatarPath)) {
            return;
        }

        File::delete(public_path($avatarPath));
    }
}
