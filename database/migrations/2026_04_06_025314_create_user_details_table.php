<?php

use App\Enums\FamilyStatus;
use App\Enums\Sex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('birth_date')->nullable();
            $table->enum('sex', Sex::values())->nullable();
            $table->string('nationality')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('address')->nullable();
            $table->string('tel_1')->nullable();
            $table->string('tel_2')->nullable();
            $table->enum('family_status', FamilyStatus::values())->nullable();
            $table->json('children')->nullable();
            $table->date('started_work_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
