<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * docs/07 § hr_positions ⇅
     * FK job_standard_id → cmp_job_standards ditambahkan pada F2 saat tabel SKJ dibuat.
     */
    public function up(): void
    {
        Schema::create('hr_positions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40);
            $table->string('name', 200);
            $table->string('type', 20);
            $table->unsignedTinyInteger('grade_class')->nullable();
            $table->string('family', 100)->nullable();
            $table->string('tier', 20);
            $table->unsignedBigInteger('job_standard_id')->nullable();
            $table->string('source', 40);
            $table->string('external_id', 64);
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_positions');
    }
};
