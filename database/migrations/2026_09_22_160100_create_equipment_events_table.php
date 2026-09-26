<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Everything that has ever happened to a unit, in the order it happened:
     * put on the books, handed over, reassigned, taken back, serviced, written
     * off, or simply corrected. This is the whole record of a unit's past — who
     * did what to it and when — which is what a question like "what changed
     * last month" is really asking.
     */
    public function up(): void
    {
        Schema::create('equipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            // Whoever did it; empty for what the seeder and the system do.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('kind', 30)->index();
            // Field => [before, after], for the fields that actually changed.
            // Not called "changes": Eloquent keeps a property of that name, and
            // a column that shadows it is unreadable from inside the model.
            $table->json('diff')->nullable();
            // A line to read when the fields alone do not tell the story.
            $table->string('note', 200)->nullable();

            $table->timestamps();

            $table->index(['equipment_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_events');
    }
};
