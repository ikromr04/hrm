<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Time off: the kinds of it the company recognises, each with how many
     * days a year it allows, and the requests people file against them. A
     * request goes to the head of the employee's department first and to HR
     * after that, so both decisions are kept beside it.
     */
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            // How many days of it a year; unlimited kinds leave this empty.
            $table->unsignedSmallInteger('days_per_year')->nullable();
            // The longest single spell, where the law or the rules cap it:
            // annual leave may be split, but no part may run past 14 days.
            $table->unsignedSmallInteger('max_part_days')->nullable();
            // Which badge colour the lists give it.
            $table->string('tone', 20)->default('neutral');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();

            $table->date('started_on');
            $table->date('ended_on');
            // Calendar days, both ends counted; kept so a balance is a sum.
            $table->unsignedSmallInteger('days');
            $table->string('note', 300)->nullable();

            // Where it is: with the head, with HR, settled either way, or
            // withdrawn by whoever asked.
            $table->enum('status', ['pending_head', 'pending_hr', 'approved', 'rejected', 'cancelled'])->default('pending_head')->index();

            // Who said yes at each step, and when.
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('head_decided_at')->nullable();
            $table->foreignId('hr_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_decided_at')->nullable();
            // Why it was turned down, in the words of whoever turned it down.
            $table->string('decision_note', 300)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'started_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};
