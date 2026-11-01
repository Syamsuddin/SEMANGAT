<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § cmp_job_standards (SKJ, KOM-01) — kolom integrasi NULLable: bisa input berkas sampai lapisan data bersama siap. */
    public function up(): void
    {
        Schema::create('cmp_job_standards', function (Blueprint $table) {
            $table->id();
            $table->string('position_code', 40);
            $table->string('name', 200);
            $table->date('effective_on');
            $table->string('source', 40)->nullable();
            $table->string('external_id', 64)->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id']);
            $table->index(['position_code', 'effective_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_job_standards');
    }
};
