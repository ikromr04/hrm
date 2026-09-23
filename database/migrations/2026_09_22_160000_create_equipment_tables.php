<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company hardware: a directory of categories ("Ноутбуки", "Мониторы") and
     * the units themselves. A unit belongs to the company, not to a person —
     * it is bought, handed out, returned, repaired and eventually written off,
     * so it exists on its own and merely points at whoever holds it now.
     */
    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_type_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('maker', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('inventory_number', 50)->unique();

            $table->enum('status', ['issued', 'stock', 'repair', 'written_off'])->default('stock')->index();

            // Who holds it now: a person or a whole department, or nobody at
            // all while it sits in stock or at a repair shop.
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('holder_department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->date('issued_at')->nullable();
            $table->date('written_off_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_types');
    }
};
