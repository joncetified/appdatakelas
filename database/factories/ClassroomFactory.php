<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Kelas '.fake()->unique()->bothify('??-##'),
            'location' => fake()->randomElement([
                'Gedung A - Lantai 1',
                'Gedung B - Lantai 2',
                'Lab Timur',
                'Sayap Barat',
            ]),
            'description' => fake()->sentence(),
            'leader_id' => null,
            'homeroom_teacher_id' => null,
        ];
    }
}
