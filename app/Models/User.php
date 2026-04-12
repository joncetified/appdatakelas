<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use App\Models\Concerns\HasAuditTrail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use MustVerifyEmailTrait;
    use HasAuditTrail;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_PRINCIPAL = 'kepala_sekolah';
    public const ROLE_CLASS_LEADER = 'ketua_kelas';
    public const ROLE_HOMEROOM_TEACHER = 'wali_kelas';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'whatsapp_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_PRINCIPAL => 'Kepala Sekolah',
            self::ROLE_CLASS_LEADER => 'Ketua Kelas',
            self::ROLE_HOMEROOM_TEACHER => 'Wali Kelas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function manageableRoleOptionsFor(self $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return self::roleOptions();
        }

        if ($actor->isAdmin()) {
            return Arr::except(self::roleOptions(), [self::ROLE_SUPER_ADMIN]);
        }

        return [];
    }

    public function ledClassroom(): HasOne
    {
        return $this->hasOne(Classroom::class, 'leader_id');
    }

    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    public function submittedReports(): HasMany
    {
        return $this->hasMany(InfrastructureReport::class, 'reported_by_id');
    }

    public function verifiedReports(): HasMany
    {
        return $this->hasMany(InfrastructureReport::class, 'verified_by_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')->withTimestamps();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public function isPrincipal(): bool
    {
        return $this->hasRole(self::ROLE_PRINCIPAL);
    }

    public function isClassLeader(): bool
    {
        return $this->hasRole(self::ROLE_CLASS_LEADER);
    }

    public function isHomeroomTeacher(): bool
    {
        return $this->hasRole(self::ROLE_HOMEROOM_TEACHER);
    }

    public function canManageMasterData(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN);
    }

    public function canBeManagedBy(self $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->isAdmin()) {
            return ! $this->isSuperAdmin();
        }

        return false;
    }

    public function hasOverviewAccess(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_PRINCIPAL,
        );
    }

    public function canViewIncomeDashboard(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN, self::ROLE_MANAGER);
    }

    public function requiresEmailVerification(): bool
    {
        return $this->hasRole(self::ROLE_CLASS_LEADER, self::ROLE_HOMEROOM_TEACHER);
    }

    public function hasVerifiedEmail(): bool
    {
        if (! $this->requiresEmailVerification()) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    /**
     * @return list<string>
     */
    public static function defaultPermissionSlugsForRole(string $role): array
    {
        return match ($role) {
            self::ROLE_SUPER_ADMIN => array_column(Permission::defaults(), 'slug'),
            self::ROLE_ADMIN => [
                'dashboard.view',
                'reports.view',
                'analytics.view',
                'users.manage',
                'classrooms.manage',
                'settings.manage',
                'tools.manage',
                'exports.manage',
                'reports.delete',
            ],
            self::ROLE_MANAGER => [
                'dashboard.view',
                'reports.view',
                'analytics.view',
                'income.view',
                'income.manage',
            ],
            self::ROLE_PRINCIPAL => [
                'dashboard.view',
                'reports.view',
                'analytics.view',
            ],
            self::ROLE_HOMEROOM_TEACHER => [
                'dashboard.view',
                'reports.view',
                'reports.verify',
            ],
            self::ROLE_CLASS_LEADER => [
                'dashboard.view',
                'reports.view',
                'reports.create',
                'reports.edit',
                'reports.delete',
            ],
            default => ['dashboard.view'],
        };
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $relation = $this->relationLoaded('permissions')
            ? $this->permissions
            : $this->permissions()->get(['permissions.slug']);

        return $relation->contains('slug', $slug);
    }

    /**
     * @param  list<string>  $slugs
     */
    public function syncPermissionsBySlugs(array $slugs): void
    {
        $ids = Permission::query()
            ->whereIn('slug', array_values(array_unique($slugs)))
            ->pluck('id')
            ->all();

        $this->permissions()->sync($ids);
        $this->unsetRelation('permissions');
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleOptions()[$this->role] ?? Str::headline($this->role);
    }
}
