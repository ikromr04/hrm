<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photographs of a unit, taken when somebody looked it over or worked on
     * it. They belong to the journal entry that records the occasion rather
     * than to the unit, so the next check adds its own and the earlier ones
     * stay where they were — a record of what the thing looked like that day.
     */
    public function up(): void
    {
        Schema::create('equipment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_event_id')->nullable()->constrained()->cascadeOnDelete();
            // Set as well when the pictures came with a piece of service work,
            // so they are shown on that record and not only in the journal.
            $table->foreignId('equipment_repair_id')->nullable()->constrained()->cascadeOnDelete();

            // The upload itself, and the scaled copy the interface shows.
            $table->string('path');
            $table->string('preview');

            $table->timestamps();

            $table->index(['equipment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_photos');
    }
};
