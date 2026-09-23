<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the employee worked before; several records per person, known to
     * the month (private, like user_details).
     */
    public function up(): void
    {
        Schema::create('user_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('organization', 200);
            $table->string('position', 150);
            $table->string('country', 100);
            $table->unsignedTinyInteger('started_month');
            $table->unsignedSmallInteger('started_year');
            // Both empty while the person still works there.
            $table->unsignedTinyInteger('ended_month')->nullable();
            $table->unsignedSmallInteger('ended_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_work_experiences');
    }
};
