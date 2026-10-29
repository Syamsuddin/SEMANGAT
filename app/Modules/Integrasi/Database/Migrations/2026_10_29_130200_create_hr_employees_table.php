<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § hr_employees ⇅ — pegawai tidak pernah dihapus, hanya status=inactive. */
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->char('nip', 18)->unique();
            $table->string('name', 150);
            $table->string('employment_type', 10);
            $table->string('status', 12);
            $table->foreignId('org_unit_id')->constrained('hr_org_units')->restrictOnDelete();
            $table->foreignId('opd_id')->constrained('hr_org_units')->restrictOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('hr_employees')->restrictOnDelete();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('source', 40);
            $table->string('external_id', 64);
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['source', 'external_id']);
            $table->index(['opd_id', 'status']);
            $table->index('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
