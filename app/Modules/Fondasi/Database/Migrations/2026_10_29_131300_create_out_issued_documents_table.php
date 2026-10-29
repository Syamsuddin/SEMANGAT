<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § out_issued_documents (FON-09). */
    public function up(): void
    {
        Schema::create('out_issued_documents', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('type', 40);
            $table->string('documentable_type', 120);
            $table->unsignedBigInteger('documentable_id');
            $table->foreignId('employee_id')->nullable()->constrained('hr_employees')->restrictOnDelete();
            $table->string('file_path', 255);
            $table->char('sha256', 64);
            $table->dateTime('issued_at');
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['documentable_type', 'documentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_issued_documents');
    }
};
