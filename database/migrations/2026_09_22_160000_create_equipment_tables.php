<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company hardware handed out to employees: a directory of kinds
     * ("Монитор", "Ноутбук") and the actual units, private like user_details.
     */
    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('user_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_type_id')->constrained()->cascadeOnDelete();
            // Model, configuration, anything worth noting about this unit.
            $table->string('description', 255)->nullable();
            // One physical unit belongs to one person at a time.
            $table->string('inventory_number', 50)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_equipment');
        Schema::dropIfExists('equipment_types');
    }
};
