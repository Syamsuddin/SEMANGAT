<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penugasan peran manual tercatat (keputusan pemilik produk 2026-10-29; docs/07 § sys_role_assignments).
     * Peran turunan (asn, atasan) TIDAK pernah disimpan di sini.
     */
    public function up(): void
    {
        Schema::create('sys_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 40);
            $table->string('scope_type', 20)->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('reason', 255);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_role_assignments');
    }
};
