<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § hr_skp_summaries ⇅ (SKPRingkas — BACA SAJA, INT-02). Tanpa kolom nilai/predikat. */
    public function up(): void
    {
        Schema::create('hr_skp_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->smallInteger('period_year');
            $table->text('dialogue_notes')->nullable();
            $table->text('realization_notes')->nullable();
            $table->string('source', 40);
            $table->string('external_id', 64);
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['source', 'external_id']);
            $table->unique(['employee_id', 'period_year', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_skp_summaries');
    }
};
