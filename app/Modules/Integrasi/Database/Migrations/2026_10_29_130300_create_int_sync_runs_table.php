<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * docs/07 § int_sync_runs / int_sync_failures (INT-08).
     * source_ref: rujukan berkas/parameter agar run dapat diulang dari panel (tambahan F1, dicatat di docs/07).
     */
    public function up(): void
    {
        Schema::create('int_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('adapter', 60);
            $table->string('source_ref', 255)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->string('status', 12);
            $table->unsignedInteger('rows_ok')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['adapter', 'started_at']);
        });

        Schema::create('int_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained('int_sync_runs')->restrictOnDelete();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('external_id', 64)->nullable();
            $table->json('payload');
            $table->text('error');
            $table->dateTime('retried_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('int_sync_failures');
        Schema::dropIfExists('int_sync_runs');
    }
};
