<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A right given to, or taken from, one person in particular.
 *
 * Rights normally travel with a position: every "Аналитик" may do the same
 * things. Now and then one person needs something their position does not carry
 * — or must not have something it does. Such an exception is kept here and beats
 * the position either way, so there is one place to look when the question is
 * why this colleague, and not their neighbour, can do a thing.
 *
 * The right is stored by name rather than by id: the list of rights lives in
 * code (App\Support\Access), and an exception should survive the permissions
 * table being seeded again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission');
            // True: given on top of the position. False: taken away from it.
            $table->boolean('allowed');
            $table->timestamps();

            $table->unique(['user_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_overrides');
    }
};
