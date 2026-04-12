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
    public function test_root_redirects_guest_to_setup_when_no_user_exists(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('setup.admin.create'));
    }

    public function test_root_redirects_guest_to_login_when_user_exists(): void
    {
        User::factory()->superAdmin()->create();

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
