<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 catatan migrasi F1: FK hr_positions.job_standard_id → cmp_job_standards ditambahkan pada F2. Aditif. */
    public function up(): void
    {
        Schema::table('hr_positions', function (Blueprint $table) {
            $table->foreign('job_standard_id')->references('id')->on('cmp_job_standards')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hr_positions', function (Blueprint $table) {
            $table->dropForeign(['job_standard_id']);
        });
    }
};
