<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § out_jp_records (CatatanJP, JPR-01…04). Saldo = SUM(jp) WHERE status='verified'. */
    public function up(): void
    {
        Schema::create('out_jp_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->unsignedSmallInteger('year');
            $table->string('sourceable_type', 120)->nullable();
            $table->unsignedBigInteger('sourceable_id')->nullable();
            $table->string('title', 255);
            $table->unsignedTinyInteger('portion');
            $table->string('mode', 15);
            $table->decimal('jp', 5, 1);
            $table->date('activity_start');
            $table->date('activity_end');
            $table->string('status', 10)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->dateTime('verified_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'year', 'status']);
            $table->index(['sourceable_type', 'sourceable_id']);
        });

        DB::statement('ALTER TABLE out_jp_records ADD CONSTRAINT out_jp_records_jp_positive CHECK (jp > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('out_jp_records');
    }
};
