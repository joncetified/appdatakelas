<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->index();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role', 'permission_id']);
        });

        Permission::syncDefaults();

        $permissionIdsBySlug = Permission::query()->pluck('id', 'slug')->all();
        $rows = [];

        foreach (array_keys(User::roleOptions()) as $role) {
            foreach (User::defaultPermissionSlugsForRole($role) as $slug) {
                $permissionId = $permissionIdsBySlug[$slug] ?? null;

                if (! $permissionId) {
                    continue;
                }

                $rows[] = [
                    'role' => $role,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('role_permissions')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
