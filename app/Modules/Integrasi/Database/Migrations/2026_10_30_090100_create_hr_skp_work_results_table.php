<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § hr_skp_work_results ⇅ (RHK). Kolom predikat/nilai DILARANG (docs/02). */
    public function up(): void
    {
        Schema::create('hr_skp_work_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_summary_id')->constrained('hr_skp_summaries')->restrictOnDelete();
            $table->string('code', 40);
            $table->text('description');
            $table->text('special_expectation')->nullable();
            $table->text('realization')->nullable();
            $table->string('source', 40);
            $table->string('external_id', 64);
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_skp_work_results');
    }
};
