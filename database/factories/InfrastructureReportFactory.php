<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\InfrastructureReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InfrastructureReport>
 */
class InfrastructureReportFactory extends Factory
{
    protected $model = InfrastructureReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'reported_by_id' => User::factory()->classLeader(),
            'verified_by_id' => null,
            'report_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'student_count' => fake()->numberBetween(20, 40),
            'teacher_count' => fake()->numberBetween(1, 3),
            'status' => InfrastructureReport::STATUS_SUBMITTED,
            'notes' => fake()->sentence(),
            'verification_notes' => null,
            'verified_at' => null,
        ];
    }
}
