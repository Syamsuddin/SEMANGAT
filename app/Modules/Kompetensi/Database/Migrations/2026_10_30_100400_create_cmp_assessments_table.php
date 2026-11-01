<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * docs/07 § cmp_assessments (HasilUji — Sensitivitas Tinggi). Kolom 🔒 disimpan text, di-cast encrypted.
     * rejected_at/rejection_note: keputusan pemilik produk 2026-10-30 (KOM-06 — asesmen mandiri ditolak atasan).
     */
    public function up(): void
    {
        Schema::create('cmp_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->foreignId('competency_id')->constrained('cmp_competencies')->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('method', 30);
            $table->enum('confidence', ['verified', 'provisional', 'indicative']);
            $table->string('institution', 150)->nullable();
            $table->date('assessed_on');
            $table->date('valid_until')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('hr_employees')->restrictOnDelete();
            $table->dateTime('validated_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->string('rejection_note', 255)->nullable();
            $table->text('report_file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'competency_id', 'assessed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_assessments');
    }
};
