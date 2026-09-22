<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employment status: people who left stay in the database, apart from
     * the working staff. Deleting an employee removes the row altogether.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'transferred', 'fired'])->default('active')->after('sex')->index();
            $table->date('status_changed_at')->nullable()->after('status');
            // Where the person was transferred to, or why they were let go.
            $table->string('status_note')->nullable()->after('status_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'status_changed_at', 'status_note']);
        });
    }
};
