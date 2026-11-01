<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § pln_idp_checkins (IDP-06) — satu check-in per triwulan per IDP. */
    public function up(): void
    {
        Schema::create('pln_idp_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idp_id')->constrained('pln_idps')->cascadeOnDelete();
            $table->date('checkin_on');
            $table->unsignedTinyInteger('quarter');
            $table->text('employee_note');
            $table->text('supervisor_note')->nullable();
            $table->json('progress');
            $table->timestamps();

            $table->unique(['idp_id', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pln_idp_checkins');
    }
};
