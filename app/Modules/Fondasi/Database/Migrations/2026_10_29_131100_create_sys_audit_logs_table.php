<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * docs/07 § sys_audit_logs (append-only, tanpa updated_at).
     * Produksi: user DB aplikasi hanya diberi INSERT/SELECT (docs/21, docs/25).
     */
    public function up(): void
    {
        Schema::create('sys_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 40);
            $table->string('auditable_type', 120);
            $table->unsignedBigInteger('auditable_id');
            $table->string('reason', 255)->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45);
            $table->dateTime('created_at', 3);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_audit_logs');
    }
};
