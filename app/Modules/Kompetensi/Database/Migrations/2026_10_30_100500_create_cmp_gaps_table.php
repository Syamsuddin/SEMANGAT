<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § cmp_gaps — materialisasi, dihitung ulang oleh RecalculateGaps (keputusan A4). */
    public function up(): void
    {
        Schema::create('cmp_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->foreignId('competency_id')->constrained('cmp_competencies')->restrictOnDelete();
            $table->unsignedTinyInteger('required_level');
            $table->unsignedTinyInteger('actual_level')->nullable();
            $table->unsignedTinyInteger('gap');
            $table->enum('confidence', ['verified', 'provisional', 'indicative']);
            $table->foreignId('assessment_id')->nullable()->constrained('cmp_assessments')->restrictOnDelete();
            $table->dateTime('calculated_at');
            $table->timestamps();
            $table->unique(['employee_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_gaps');
    }
};
