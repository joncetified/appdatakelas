<?php

use App\Models\InfrastructureReport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('infrastructure_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('report_date');
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('teacher_count')->default(0);
            $table->string('status')->default(InfrastructureReport::STATUS_SUBMITTED);
            $table->text('notes')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infrastructure_reports');
    }
};
