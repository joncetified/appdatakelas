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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('group');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        Permission::syncDefaults();

        $permissionIdsBySlug = Permission::query()->pluck('id', 'slug')->all();

        DB::table('users')->select('id', 'role')->orderBy('id')->get()->each(function (object $user) use ($permissionIdsBySlug): void {
            $rows = collect(User::defaultPermissionSlugsForRole((string) $user->role))
                ->map(fn ($slug) => $permissionIdsBySlug[$slug] ?? null)
                ->filter()
                ->map(fn ($permissionId) => [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            if ($rows !== []) {
                DB::table('user_permissions')->insert($rows);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permissions');
    }
};
