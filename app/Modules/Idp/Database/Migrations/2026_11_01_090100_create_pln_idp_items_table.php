<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § pln_idp_items — 2–3 per IDP saat diajukan (ditegakkan di Action + tes). */
    public function up(): void
    {
        Schema::create('pln_idp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idp_id')->constrained('pln_idps')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('cmp_competencies')->restrictOnDelete();
            $table->foreignId('gap_id')->nullable()->constrained('cmp_gaps')->nullOnDelete();
            $table->unsignedTinyInteger('start_level');
            $table->unsignedTinyInteger('target_level');
            $table->foreignId('skp_work_result_id')->nullable()->constrained('hr_skp_work_results')->restrictOnDelete();
            $table->text('success_indicator')->nullable();
            $table->foreignId('mentor_employee_id')->nullable()->constrained('hr_employees')->restrictOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['idp_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pln_idp_items');
    }
};
