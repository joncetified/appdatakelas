<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasAuditTrail;
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'location',
        'description',
        'leader_id',
        'homeroom_teacher_id',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(InfrastructureReport::class);
    }

    public function latestReport(): HasOne
    {
        return $this->hasOne(InfrastructureReport::class)->latestOfMany('report_date');
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
}
