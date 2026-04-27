<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Database\Factories\InfrastructureReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InfrastructureReport extends Model
{
    /** @use HasFactory<InfrastructureReportFactory> */
    use HasAuditTrail;

    use HasFactory;
    use SoftDeletes;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    public const STATUS_VERIFIED = 'verified';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'classroom_id',
        'reported_by_id',
        'verified_by_id',
        'report_date',
        'student_count',
        'teacher_count',
        'status',
        'notes',
        'verification_notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Menunggu Verifikasi',
            self::STATUS_REVISION_REQUESTED => 'Perlu Revisi',
            self::STATUS_VERIFIED => 'Terverifikasi',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InfrastructureReportItem::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasOverviewAccess()) {
            return $query;
        }

        if ($user->isClassLeader()) {
            return $query->whereHas('classroom', fn (Builder $builder) => $builder->where('leader_id', $user->id));
        }

        return $query->whereHas('classroom', fn (Builder $builder) => $builder->where('homeroom_teacher_id', $user->id));
    }

    public function isEditable(): bool
    {
        return $this->status !== self::STATUS_VERIFIED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline($this->status);
    }

    public function getTotalUnitsAttribute(): int
    {
        return (int) $this->items->sum('total_units');
    }

    public function getDamagedUnitsAttribute(): int
    {
        return (int) $this->items->sum('damaged_units');
    }
}
