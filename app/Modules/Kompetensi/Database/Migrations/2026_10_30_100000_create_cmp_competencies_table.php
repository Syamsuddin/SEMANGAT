<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § cmp_competencies (kamus, KOM-02) */
    public function up(): void
    {
        Schema::create('cmp_competencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 200);
            $table->string('type', 20);
            $table->text('description')->nullable();
            $table->json('level_descriptors')->nullable();
            $table->string('steward', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_competencies');
    }
};
