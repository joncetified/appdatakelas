<?php

namespace Database\Factories;

use App\Models\InfrastructureReport;
use App\Models\InfrastructureReportItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InfrastructureReportItem>
 */
class InfrastructureReportItemFactory extends Factory
{
    protected $model = InfrastructureReportItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalUnits = fake()->numberBetween(5, 40);
        $damagedUnits = fake()->numberBetween(0, min(5, $totalUnits));

        return [
            'infrastructure_report_id' => InfrastructureReport::factory(),
            'item_name' => fake()->randomElement([
                'Komputer',
                'Meja',
                'Kursi',
                'Proyektor',
                'Papan Tulis',
            ]),
            'total_units' => $totalUnits,
            'damaged_units' => $damagedUnits,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
