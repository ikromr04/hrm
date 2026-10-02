<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the bell shows: a line for a person about something that happened
     * without them — a unit handed to them, a colleague added, a card changed.
     * The table is the one Laravel's database channel writes to, as it comes.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            // What happened, as the list reads it: the kind, the sentence and
            // the page it leads to. See App\Notifications\InAppNotification.
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
