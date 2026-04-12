<?php

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
        Schema::create('infrastructure_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infrastructure_report_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('total_units');
            $table->unsignedInteger('damaged_units')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infrastructure_report_items');
    }
};
