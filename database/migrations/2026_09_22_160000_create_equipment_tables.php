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
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('inventory_number', 50)->unique();

            // What is inside it, for the card's "Характеристики".
            $table->string('processor', 100)->nullable();
            $table->string('memory', 100)->nullable();

            // The state it was last seen in, and when it is due to be looked at again.
            $table->string('condition', 200)->nullable();
            $table->date('checked_at')->nullable();
            $table->date('next_inventory_at')->nullable();

            // What comes with it: "Блок питания 65 Вт", "Сумка", a bag of labels.
            $table->json('accessories')->nullable();

            $table->enum('status', ['issued', 'stock', 'repair', 'written_off'])->default('stock')->index();

            // Who holds it now: a person or a whole department, or nobody at
            // all while it sits in stock or at a repair shop.
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('holder_department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->date('issued_at')->nullable();
            $table->date('written_off_at')->nullable();

            $table->timestamps();
        });

        // Where a unit has been: one row per spell with somebody, the open one
        // being where it is now. A row with no holder is a spell in stock, so
        // the card's history reads "Фарход Рахимов", then "Склад", and so on.
        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();

            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('holder_department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->date('issued_at');
            $table->date('returned_at')->nullable();
            // Filled in when it comes back: "Новое, в упаковке", "Царапина на крышке".
            $table->string('condition_on_return', 200)->nullable();

            $table->timestamps();
        });

        // Every visit to a repair shop, planned maintenance included.
        Schema::create('equipment_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();

            $table->string('kind', 150);
            $table->date('started_at');
            // Still away while this is empty.
            $table->date('ended_at')->nullable();
            $table->string('note', 200)->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_repairs');
        Schema::dropIfExists('equipment_assignments');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_types');
    }
};
