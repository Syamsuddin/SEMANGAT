<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** docs/07 § hr_org_units ⇅ */
    public function up(): void
    {
        Schema::create('hr_org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('hr_org_units')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 200);
            $table->string('level', 20);
            $table->foreignId('opd_id')->nullable()->constrained('hr_org_units')->restrictOnDelete();
            $table->string('source', 40);
            $table->string('external_id', 64);
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_org_units');
    }
};
