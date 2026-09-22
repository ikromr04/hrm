<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the employee studied; several records per person (private, like
     * user_details).
     */
    public function up(): void
    {
        Schema::create('user_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('institution', 200);
            $table->string('faculty', 150);
            $table->string('specialty', 150);
            $table->unsignedSmallInteger('started_year');
            // Empty while still studying.
            $table->unsignedSmallInteger('graduated_year')->nullable();
            $table->string('diploma_number', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_educations');
    }
};
