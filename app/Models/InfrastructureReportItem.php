<?php

namespace App\Models;

use Database\Factories\InfrastructureReportItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrastructureReportItem extends Model
{
    /** @use HasFactory<InfrastructureReportItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'infrastructure_report_id',
        'item_name',
        'total_units',
        'damaged_units',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_units' => 'integer',
            'damaged_units' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(InfrastructureReport::class, 'infrastructure_report_id');
    }

    public function getGoodUnitsAttribute(): int
    {
        return max(0, $this->total_units - $this->damaged_units);
    }
}
