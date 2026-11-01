<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § out_certificates (JPR-02). UNIQUE(employee_id, sha256) = deteksi duplikat berkas. */
    public function up(): void
    {
        Schema::create('out_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->foreignId('jp_record_id')->unique()->constrained('out_jp_records')->cascadeOnDelete();
            $table->string('issuer', 200);
            $table->string('number', 100)->nullable();
            $table->string('file_path', 255);
            $table->char('sha256', 64);
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_certificates');
    }
};
