<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_PIXEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnRk4sAAAAASUVORK5CYII=';

    public function test_authenticated_user_can_open_profile_page(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Kelola akun Anda sendiri');
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->manager()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@sekolah.test',
            'whatsapp_number' => '081200000111',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@sekolah.test',
            'whatsapp_number' => '081200000222',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@sekolah.test', $user->email);
        $this->assertSame('081200000222', $user->whatsapp_number);
    }

    public function test_user_can_upload_avatar_from_profile_page(): void
    {
        $user = User::factory()->manager()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp_number' => $user->whatsapp_number,
            'avatar' => UploadedFile::fake()->createWithContent('avatar.png', base64_decode(self::PNG_PIXEL)),
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertTrue(File::exists(public_path($user->avatar_path)));

        File::delete(public_path($user->avatar_path));
    }

    public function test_user_can_remove_avatar_from_profile_page(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp_number' => $user->whatsapp_number,
            'avatar' => UploadedFile::fake()->createWithContent('avatar.png', base64_decode(self::PNG_PIXEL)),
        ]);

        $user->refresh();
        $this->assertNotNull($user->avatar_path);

        $avatarPath = $user->avatar_path;

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp_number' => $user->whatsapp_number,
            'remove_avatar' => '1',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNull($user->avatar_path);
        $this->assertFalse(File::exists(public_path($avatarPath)));
    }

    public function test_email_change_for_role_that_requires_verification_resets_status(): void
    {
        Notification::fake();

        $user = User::factory()->classLeader()->create([
            'email' => 'ketua@sekolah.test',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'ketua-baru@sekolah.test',
            'whatsapp_number' => $user->whatsapp_number,
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user->refresh();

        $this->assertSame('ketua-baru@sekolah.test', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_can_change_password_from_profile_page(): void
    {
        $user = User::factory()->manager()->create([
            'password' => 'PasswordLama123!',
        ]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'PasswordLama123!',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertTrue(Hash::check('PasswordBaru123!', $user->password));
    }

    public function test_wrong_current_password_cannot_change_password(): void
    {
        $user = User::factory()->manager()->create([
            'password' => 'PasswordLama123!',
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.password.update'), [
            'current_password' => 'SalahBanget123!',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('current_password');

        $user->refresh();

        $this->assertTrue(Hash::check('PasswordLama123!', $user->password));
    }
}
