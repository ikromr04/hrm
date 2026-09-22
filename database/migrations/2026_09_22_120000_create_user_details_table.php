<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Private employee data. Kept out of the users table so it is never
     * serialized along with the public profile by accident.
     */
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->date('hired_at')->nullable();

            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('nationality')->nullable();

            $table->string('passport_series', 16)->nullable();
            $table->string('passport_number', 32)->nullable();
            $table->date('passport_issued_at')->nullable();
            $table->string('passport_issued_by')->nullable();

            $table->enum('marital_status', ['single', 'married'])->nullable();

            $table->string('home_address')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('sos_phone', 32)->nullable();

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
