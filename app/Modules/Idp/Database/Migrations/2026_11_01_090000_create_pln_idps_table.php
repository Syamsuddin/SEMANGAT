<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * docs/07 § pln_idps. UNIQUE(employee_id, period_year) "di antara yang belum cancelled" ditegakkan lewat
     * kolom bangkitan `open_period_year` (NULL bila cancelled; NULL tidak bentrok di indeks UNIQUE MySQL/MariaDB).
     */
    public function up(): void
    {
        Schema::create('pln_idps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('hr_employees')->restrictOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->string('status', 12);
            $table->string('template', 20)->default('standard');
            $table->text('career_aspiration')->nullable();
            $table->unsignedSmallInteger('target_jp')->default(20);
            $table->text('supervisor_note')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->text('closing_reflection')->nullable();
            $table->text('closing_assessment')->nullable();
            $table->unsignedTinyInteger('unlinked_items_count')->default(0);
            $table->unsignedSmallInteger('open_period_year')->nullable()
                ->storedAs("CASE WHEN status = 'cancelled' THEN NULL ELSE period_year END");
            $table->timestamps();

            $table->unique(['employee_id', 'open_period_year']);
            $table->index(['supervisor_id', 'status']);
            $table->index(['status', 'unlinked_items_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pln_idps');
    }
};
