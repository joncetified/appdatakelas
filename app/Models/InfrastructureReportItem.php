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

    public function getCriticalThresholdAttribute(): int
    {
        return max(1, (int) ceil($this->total_units * 0.2));
    }

    public function getDamagePercentageAttribute(): int
    {
        if ($this->total_units <= 0) {
            return 0;
        }

        return (int) round(($this->damaged_units / $this->total_units) * 100);
    }

    public function getIsCriticalStockAttribute(): bool
    {
        if ($this->total_units <= 0 || $this->damaged_units <= 0) {
            return false;
        }

        return $this->good_units === 0
            || $this->good_units <= $this->critical_threshold
            || $this->damage_percentage >= 50;
    }

    public function getStockStatusLabelAttribute(): string
    {
        if ($this->is_critical_stock) {
            return $this->good_units === 0 ? 'Stok habis' : 'Stok kritis';
        }

        if ($this->damaged_units > 0) {
            return 'Perlu pemantauan';
        }

        return 'Aman';
    }
}
