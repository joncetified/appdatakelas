<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_root_redirects_guest_to_login_when_no_user_exists(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_root_redirects_guest_to_login_when_user_exists(): void
    {
        User::factory()->superAdmin()->create();

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_sees_custom_404_page(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada');

        $response
            ->assertStatus(404)
            ->assertSee('Error 404')
            ->assertSee('Halaman yang Anda cari tidak ditemukan.')
            ->assertSee('Masuk ke Sistem');
    }
}
