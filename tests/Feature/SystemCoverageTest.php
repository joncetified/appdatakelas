<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_core_pages(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $classroom = Classroom::factory()->create([
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $report = InfrastructureReport::factory()->create([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'verified_by_id' => $homeroomTeacher->id,
            'status' => InfrastructureReport::STATUS_VERIFIED,
        ]);

        $report->items()->create([
            'item_name' => 'Komputer',
            'total_units' => 20,
            'damaged_units' => 1,
            'notes' => 'Unit utama lab.',
        ]);

        IncomeEntry::query()->create([
            'title' => 'SPP April',
            'description' => 'Pembayaran SPP bulanan.',
            'amount' => 1200000,
            'entry_date' => now()->toDateString(),
        ]);

        $routes = [
            route('dashboard'),
            route('reports.index'),
            route('reports.show', $report),
            route('income.index'),
            route('admin.users.index'),
            route('admin.users.create'),
            route('admin.classrooms.index'),
            route('admin.classrooms.create'),
            route('admin.permissions.index'),
            route('admin.permissions.edit', $leader),
            route('admin.settings.edit'),
            route('admin.activity.index'),
            route('admin.trash.index'),
            route('admin.tools.index'),
        ];

        foreach ($routes as $route) {
            $this->actingAs($superAdmin)->get($route)->assertOk();
        }
    }

    public function test_super_admin_can_update_site_settings(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
            'company_name' => 'Sekolah Permata Harapan',
            'address' => 'Jl. Contoh No. 1',
            'manager_name' => 'Rudi Hartono',
            'contact_email' => 'info@permataharapan.test',
            'contact_phone' => '021123456',
            'contact_whatsapp' => '08123456789',
            'discord_webhook_url' => '',
            'google_recaptcha_site_key' => 'site-key',
            'google_recaptcha_secret_key' => 'secret-key',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('site_settings', [
            'company_name' => 'Sekolah Permata Harapan',
            'contact_email' => 'info@permataharapan.test',
            'google_recaptcha_site_key' => 'site-key',
        ]);
    }

    public function test_super_admin_can_create_delete_and_restore_income(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $createResponse = $this->actingAs($superAdmin)->post(route('income.store'), [
            'title' => 'Dana Operasional',
            'description' => 'Pemasukan operasional.',
            'amount' => 500000,
            'entry_date' => now()->toDateString(),
        ]);

        $createResponse->assertRedirect(route('income.index'));

        $entry = IncomeEntry::query()->firstOrFail();

        $deleteResponse = $this->actingAs($superAdmin)->delete(route('income.destroy', $entry));

        $deleteResponse->assertRedirect(route('income.index'));
        $this->assertSoftDeleted('income_entries', ['id' => $entry->id]);

        $restoreResponse = $this->actingAs($superAdmin)->post(route('admin.trash.income.restore', $entry->id));

        $restoreResponse->assertRedirect();
        $this->assertDatabaseHas('income_entries', [
            'id' => $entry->id,
            'deleted_at' => null,
        ]);
    }

    public function test_super_admin_can_create_backup_file(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $backupDirectory = storage_path('app/backups');

        File::deleteDirectory($backupDirectory);

        $response = $this->actingAs($superAdmin)->post(route('admin.tools.backups.create'));

        $response->assertRedirect();
        $this->assertTrue(File::exists($backupDirectory));
        $this->assertCount(1, File::files($backupDirectory));
    }

    public function test_admin_import_cannot_modify_super_admin_via_csv(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Akun Inti',
            'email' => 'superadmin@sekolah.test',
        ]);
        $admin = User::factory()->admin()->create();

        $csv = implode("\n", [
            'name,email,role,whatsapp_number,permissions,deleted_at',
            'Diubah,superadmin@sekolah.test,admin,081200000000,dashboard.view,',
        ]);

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.users'), [
            'users_file' => $file,
        ]);

        $response->assertRedirect();

        $superAdmin->refresh();

        $this->assertSame(User::ROLE_SUPER_ADMIN, $superAdmin->role);
        $this->assertSame('Akun Inti', $superAdmin->name);
    }

    public function test_admin_import_cannot_escalate_own_permissions(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@sekolah.test',
        ]);

        $csv = implode("\n", [
            'name,email,role,whatsapp_number,permissions,deleted_at',
            'Admin Operasional,admin@sekolah.test,admin,081200000111,permissions.manage|trash.manage|activity.view,',
        ]);

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.users'), [
            'users_file' => $file,
        ]);

        $response->assertRedirect();

        $admin->refresh();

        $this->assertFalse($admin->hasPermission('permissions.manage'));
        $this->assertFalse($admin->hasPermission('trash.manage'));
        $this->assertFalse($admin->hasPermission('activity.view'));
        $this->assertTrue($admin->hasPermission('tools.manage'));
    }

    public function test_permission_checklist_can_block_dashboard_access(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($superAdmin)->put(route('admin.permissions.update', $manager), [
            'permissions' => [],
        ]);

        $response->assertRedirect(route('admin.permissions.index'));

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_permission_checklist_even_if_permission_is_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $admin->syncPermissionsBySlugs([
            ...User::defaultPermissionSlugsForRole(User::ROLE_ADMIN),
            'permissions.manage',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.permissions.edit', $manager))
            ->assertForbidden();
    }

    public function test_admin_cannot_reset_database(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.tools.database.reset'), [
            'confirmation' => 'RESET DATABASE',
            'name' => 'Admin Baru',
            'email' => 'baru@sekolah.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertForbidden();
    }

    public function test_self_registration_creates_unverified_account(): void
    {
        User::factory()->superAdmin()->create();

        $response = $this->post(route('register.store'), [
            'name' => 'Ketua Mandiri',
            'email' => 'ketua.mandiri@sekolah.test',
            'whatsapp_number' => '081255500000',
            'role' => User::ROLE_CLASS_LEADER,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ketua.mandiri@sekolah.test',
            'role' => User::ROLE_CLASS_LEADER,
            'email_verified_at' => null,
        ]);
    }

    public function test_whatsapp_password_reset_redirects_to_support_number(): void
    {
        User::factory()->superAdmin()->create();
        User::factory()->classLeader()->create([
            'email' => 'ketua@sekolah.test',
        ]);

        SiteSetting::query()->firstOrFail()->update([
            'contact_whatsapp' => '081234567890',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'ketua@sekolah.test',
            'channel' => 'whatsapp',
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('https://wa.me/081234567890', (string) $response->headers->get('Location'));
    }

    public function test_restore_backup_logs_out_current_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $backupPayload = json_encode([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'app' => 'InfraKelas',
            ],
            'site_settings' => [],
            'permissions' => [],
            'users' => [],
            'classrooms' => [],
            'reports' => [],
            'report_items' => [],
            'income_entries' => [],
        ], JSON_THROW_ON_ERROR);

        $file = UploadedFile::fake()->createWithContent('backup.json', $backupPayload);

        $response = $this->actingAs($superAdmin)->post(route('admin.tools.backups.restore'), [
            'backup_file' => $file,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }
}
