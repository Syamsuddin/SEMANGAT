<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § pln_idp_activities. FK program_id → lrn_programs ditambahkan di F5 saat tabelnya ada. */
    public function up(): void
    {
        Schema::create('pln_idp_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idp_item_id')->constrained('pln_idp_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('portion');
            $table->string('title', 200);
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedSmallInteger('planned_jp')->default(0);
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->string('status', 12)->default('planned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pln_idp_activities');
    }
};
