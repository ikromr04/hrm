<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company hardware: a directory of categories ("Ноутбуки", "Мониторы") and
     * the units themselves. A unit belongs to the company, not to a person —
     * it is bought, handed out, returned, serviced and eventually written off,
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

            $table->enum('status', ['issued', 'stock', 'written_off'])->default('stock')->index();

            // Who holds it now: one colleague, or nobody at all while it sits
            // on the balance sheet. A unit is never signed out to a department:
            // somebody answers for it by name.
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('issued_at')->nullable();
            $table->date('written_off_at')->nullable();

            $table->timestamps();
        });

        // Every piece of work done on a unit: maintenance as well as a repair.
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
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_types');
    }
};
