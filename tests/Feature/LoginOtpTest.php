<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_password_goes_directly_to_dashboard(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.test',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'login' => 'Admin Sekolah',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_wrong_otp_does_not_login_user(): void
    {
        Notification::fake();

        User::factory()->admin()->create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.test',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'login' => 'Admin Sekolah',
            'action' => 'otp',
        ])->assertRedirect(route('login.otp'));

        $response = $this->from(route('login.otp'))->post(route('login.otp.verify'), [
            'otp' => '000000',
        ]);

        $response
            ->assertRedirect(route('login.otp'))
            ->assertSessionHasErrors('otp');

        $this->assertGuest();
    }

    public function test_user_can_request_login_otp_by_username_without_password(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => 'Admin Sekolah',
            'action' => 'otp',
        ]);

        $response->assertRedirect(route('login.otp'));
        $this->assertGuest();

        $otp = null;

        Notification::assertSentTo(
            $user,
            LoginOtpNotification::class,
            function (LoginOtpNotification $notification) use (&$otp): bool {
                $otp = $notification->code;

                return preg_match('/^\d{6}$/', $otp) === 1;
            }
        );

        $otpResponse = $this->post(route('login.otp.verify'), [
            'otp' => $otp,
        ]);

        $otpResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_sends_otp_by_username_then_resets_password(): void
    {
        Notification::fake();

        $user = User::factory()->classLeader()->create([
            'name' => 'Ketua Kelas',
            'email' => 'ketua@sekolah.test',
            'password' => 'password-lama',
        ]);

        $response = $this->post(route('password.email'), [
            'login' => 'Ketua Kelas',
            'channel' => 'email',
        ]);

        $response->assertRedirect(route('password.otp'));

        $otp = null;

        Notification::assertSentTo(
            $user,
            PasswordResetOtpNotification::class,
            function (PasswordResetOtpNotification $notification) use (&$otp): bool {
                $otp = $notification->code;

                return preg_match('/^\d{6}$/', $otp) === 1;
            }
        );

        $otpResponse = $this->post(route('password.otp.verify'), [
            'otp' => $otp,
        ]);

        $otpResponse->assertRedirect();

        $location = (string) $otpResponse->headers->get('Location');
        $path = (string) parse_url($location, PHP_URL_PATH);
        $token = basename($path);

        $resetResponse = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'ketua@sekolah.test',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('PasswordBaru123!', $user->refresh()->password));
    }

    public function test_google_callback_links_google_id_and_logs_in_existing_user(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/auth-google-callback');

        $user = User::factory()->classLeader()->unverified()->create([
            'email' => 'ketua@sekolah.test',
            'google_id' => null,
        ]);

        $googleUser = (new SocialiteUser)
            ->setRaw(['email_verified' => true])
            ->map([
                'id' => 'google-user-123',
                'email' => 'ketua@sekolah.test',
                'name' => 'Ketua Google',
            ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('login.google.callback'));

        $response->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('google-user-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_callback_creates_first_user_when_email_is_not_registered(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/auth-google-callback');

        $googleUser = (new SocialiteUser)
            ->setRaw(['email_verified' => true])
            ->map([
                'id' => 'google-user-new',
                'email' => 'pemilik@sph.test',
                'name' => 'Pemilik SPH',
            ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('login.google.callback'));

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'pemilik@sph.test')->firstOrFail();

        $this->assertSame('Pemilik SPH', $user->name);
        $this->assertSame('google-user-new', $user->google_id);
        $this->assertSame(User::ROLE_SUPER_ADMIN, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertGreaterThan(0, $user->permissions()->count());
    }
}
