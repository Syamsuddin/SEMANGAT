<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § cmp_job_standard_items */
    public function up(): void
    {
        Schema::create('cmp_job_standard_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_standard_id')->constrained('cmp_job_standards')->restrictOnDelete();
            $table->foreignId('competency_id')->constrained('cmp_competencies')->restrictOnDelete();
            $table->unsignedTinyInteger('required_level');
            $table->timestamps();
            $table->unique(['job_standard_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_job_standard_items');
    }
};
