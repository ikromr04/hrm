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
            // Which of the drawings the interface has stands for this category,
            // picked in the directory. Empty means the plain box.
            $table->string('icon', 30)->nullable();
            // Whether a unit of this category comes with anything: a laptop has a
            // power supply and a bag, a mouse comes on its own. Where it does not,
            // the card and the form say nothing about a "Комплектация" at all.
            $table->boolean('has_accessories')->default(true);
            $table->timestamps();
        });

        // What units of a category are described by, beyond the things every
        // unit has. A monitor has a diagonal and no processor, a phone has an
        // IMEI, and which is which is decided in the directory rather than in
        // code — so a new field costs nobody a deployment.
        Schema::create('equipment_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_type_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100);
            // text, number, date, boolean, select — what the form shows and what
            // the value is checked against.
            $table->string('type', 20)->default('text');
            // For "select": the values the list offers.
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            // The order the card and the form put them in.
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestamps();

            // One field per name in a category, or the card would read twice the same.
            $table->unique(['equipment_type_id', 'name']);
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_type_id')->constrained()->cascadeOnDelete();

            // The two things every unit has, whatever it is: what it is called
            // and the number on its sticker. Everything else — the maker, the
            // model, the serial number, what is inside it — is a field of its
            // category, because which of those apply depends on the category.
            $table->string('name', 200);
            $table->string('inventory_number', 50)->unique();

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

        // What one unit has in one of its category's fields. Everything is kept
        // as text and read back through the field's type: the questions asked of
        // this table are "what does this unit have" and "what changed", never
        // "sum it up", so one column is enough and the field stays free to
        // change its type without a migration.
        Schema::create('equipment_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_field_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['equipment_id', 'equipment_field_id']);
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
        Schema::dropIfExists('equipment_field_values');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_fields');
        Schema::dropIfExists('equipment_types');
    }
};
