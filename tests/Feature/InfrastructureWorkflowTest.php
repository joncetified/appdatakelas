<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\InfrastructureReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InfrastructureWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_leader_can_submit_report_for_assigned_classroom(): void
    {
        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $classroom = Classroom::factory()->create([
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $response = $this->actingAs($leader)->post(route('reports.store'), [
            'report_date' => '2026-04-11',
            'student_count' => 32,
            'teacher_count' => 2,
            'notes' => 'Pendataan sarana awal semester.',
            'items' => [
                [
                    'item_name' => 'Komputer',
                    'total_units' => 28,
                    'damaged_units' => 2,
                    'notes' => 'Perlu penggantian keyboard.',
                ],
                [
                    'item_name' => 'Kursi',
                    'total_units' => 30,
                    'damaged_units' => 1,
                    'notes' => null,
                ],
            ],
        ]);

        $response->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('infrastructure_reports', [
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'student_count' => 32,
            'teacher_count' => 2,
            'status' => InfrastructureReport::STATUS_SUBMITTED,
        ]);

        $report = InfrastructureReport::query()->firstOrFail();

        $this->assertSame('2026-04-11', $report->report_date->format('Y-m-d'));

        $this->assertDatabaseHas('infrastructure_report_items', [
            'infrastructure_report_id' => $report->id,
            'item_name' => 'Komputer',
            'total_units' => 28,
            'damaged_units' => 2,
        ]);
    }

    public function test_homeroom_teacher_can_verify_report(): void
    {
        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $classroom = Classroom::factory()->create([
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $report = InfrastructureReport::factory()->create([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'verified_by_id' => null,
            'report_date' => '2026-04-11',
            'status' => InfrastructureReport::STATUS_SUBMITTED,
        ]);

        $report->items()->create([
            'item_name' => 'Proyektor',
            'total_units' => 1,
            'damaged_units' => 0,
            'notes' => null,
        ]);

        $response = $this->actingAs($homeroomTeacher)->post(route('reports.verification.update', $report), [
            'action' => InfrastructureReport::STATUS_VERIFIED,
            'verification_notes' => 'Data sesuai kondisi ruang kelas.',
        ]);

        $response->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('infrastructure_reports', [
            'id' => $report->id,
            'status' => InfrastructureReport::STATUS_VERIFIED,
            'verified_by_id' => $homeroomTeacher->id,
            'verification_notes' => 'Data sesuai kondisi ruang kelas.',
        ]);
    }

    public function test_super_admin_can_submit_report_for_any_classroom(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $classroom = Classroom::factory()->create([
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('reports.store'), [
            'classroom_id' => $classroom->id,
            'report_date' => '2026-04-12',
            'student_count' => 35,
            'teacher_count' => 3,
            'notes' => 'Pendataan lintas kelas oleh super admin.',
            'items' => [
                [
                    'item_name' => 'Laptop',
                    'total_units' => 12,
                    'damaged_units' => 1,
                    'notes' => 'Satu unit baterai drop.',
                ],
            ],
        ]);

        $response->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('infrastructure_reports', [
            'classroom_id' => $classroom->id,
            'reported_by_id' => $superAdmin->id,
            'student_count' => 35,
            'teacher_count' => 3,
            'status' => InfrastructureReport::STATUS_SUBMITTED,
        ]);
    }

    public function test_super_admin_can_edit_verified_report(): void
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
            'report_date' => '2026-04-10',
            'status' => InfrastructureReport::STATUS_VERIFIED,
            'verification_notes' => 'Sudah diverifikasi.',
            'verified_at' => now(),
        ]);

        $report->items()->create([
            'item_name' => 'Komputer',
            'total_units' => 20,
            'damaged_units' => 2,
            'notes' => 'Data awal.',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('reports.update', $report), [
            'classroom_id' => $classroom->id,
            'report_date' => '2026-04-10',
            'student_count' => 36,
            'teacher_count' => 2,
            'notes' => 'Revisi oleh super admin.',
            'items' => [
                [
                    'item_name' => 'Komputer',
                    'total_units' => 22,
                    'damaged_units' => 1,
                    'notes' => 'Data hasil revisi.',
                ],
            ],
        ]);

        $response->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('infrastructure_reports', [
            'id' => $report->id,
            'reported_by_id' => $superAdmin->id,
            'status' => InfrastructureReport::STATUS_SUBMITTED,
            'verification_notes' => null,
            'verified_by_id' => null,
        ]);

        $this->assertDatabaseHas('infrastructure_report_items', [
            'infrastructure_report_id' => $report->id,
            'item_name' => 'Komputer',
            'total_units' => 22,
            'damaged_units' => 1,
            'notes' => 'Data hasil revisi.',
        ]);
    }

    public function test_super_admin_can_verify_any_report(): void
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
            'verified_by_id' => null,
            'report_date' => '2026-04-11',
            'status' => InfrastructureReport::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('reports.verification.update', $report), [
            'action' => InfrastructureReport::STATUS_VERIFIED,
            'verification_notes' => 'Disahkan langsung oleh super admin.',
        ]);

        $response->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('infrastructure_reports', [
            'id' => $report->id,
            'status' => InfrastructureReport::STATUS_VERIFIED,
            'verified_by_id' => $superAdmin->id,
            'verification_notes' => 'Disahkan langsung oleh super admin.',
        ]);
    }

    public function test_guest_is_redirected_when_accessing_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_initial_super_admin_can_be_created_without_seeder(): void
    {
        $response = $this->post(route('setup.admin.store'), [
            'name' => 'Super Admin Sekolah',
            'email' => 'admin@sekolah.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'Super Admin Sekolah',
            'email' => 'admin@sekolah.test',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->assertAuthenticated();
    }

    public function test_login_page_is_accessible_when_database_has_no_user(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Buka Setup Super Admin');
    }

    public function test_login_submission_redirects_back_with_setup_message_when_database_has_no_user(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin@sekolah.test',
            'password' => 'password123',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('initial_setup');
    }

    public function test_super_admin_without_verified_email_can_still_access_dashboard(): void
    {
        $superAdmin = User::factory()->superAdmin()->unverified()->create();

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_admin_cannot_create_super_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Percobaan Super Admin',
                'email' => 'forbidden-super-admin@sekolah.test',
                'role' => User::ROLE_SUPER_ADMIN,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'forbidden-super-admin@sekolah.test',
        ]);
    }

    public function test_overview_role_can_filter_reports_by_search_term(): void
    {
        $manager = User::factory()->manager()->create();
        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $otherLeader = User::factory()->classLeader()->create();
        $otherHomeroomTeacher = User::factory()->homeroomTeacher()->create();

        $lab = Classroom::factory()->create([
            'name' => 'Lab Komputer',
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $regularClass = Classroom::factory()->create([
            'name' => 'XII IPA 1',
            'leader_id' => $otherLeader->id,
            'homeroom_teacher_id' => $otherHomeroomTeacher->id,
        ]);

        InfrastructureReport::factory()->create([
            'classroom_id' => $lab->id,
            'reported_by_id' => $leader->id,
            'report_date' => '2026-04-10',
        ]);

        InfrastructureReport::factory()->create([
            'classroom_id' => $regularClass->id,
            'reported_by_id' => $otherLeader->id,
            'report_date' => '2026-04-11',
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('reports.index', ['q' => 'Lab Komputer']));

        $response->assertOk();
        $response->assertSee('Lab Komputer');
        $response->assertDontSee('XII IPA 1');
    }

    public function test_import_users_does_not_reset_existing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->classLeader()->create([
            'email' => 'lama@sekolah.test',
            'password' => 'PasswordLama123!',
        ]);

        $csv = implode("\n", [
            'name,email,role,whatsapp_number,permissions,deleted_at',
            'Nama Baru,lama@sekolah.test,ketua_kelas,081200000000,reports.view|reports.create,',
        ]);

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.users'), [
            'users_file' => $file,
        ]);

        $response->assertRedirect();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue(Hash::check('PasswordLama123!', $user->password));
    }

    public function test_import_items_updates_existing_item_without_duplication(): void
    {
        $admin = User::factory()->admin()->create();
        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $classroom = Classroom::factory()->create([
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $report = InfrastructureReport::factory()->create([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
        ]);

        $report->items()->create([
            'item_name' => 'Komputer',
            'total_units' => 20,
            'damaged_units' => 2,
            'notes' => 'Data lama',
        ]);

        $csv = implode("\n", [
            'infrastructure_report_id,item_name,total_units,damaged_units,notes',
            "{$report->id},Komputer,24,1,Data update",
        ]);

        $file = UploadedFile::fake()->createWithContent('items.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.items'), [
            'items_file' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('infrastructure_report_items', 1);
        $this->assertDatabaseHas('infrastructure_report_items', [
            'infrastructure_report_id' => $report->id,
            'item_name' => 'Komputer',
            'total_units' => 24,
            'damaged_units' => 1,
            'notes' => 'Data update',
        ]);
    }
}
