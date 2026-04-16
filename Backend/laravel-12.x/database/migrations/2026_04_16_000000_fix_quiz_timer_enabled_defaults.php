<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ensures all quizzes have timer_enabled set to true by default (matching the column default).
     * Fixes the issue where quizzes created without explicitly setting timer_enabled would have it as false,
     * causing quiz attempts to immediately show expired (00:00 on frontend).
     */
    public function up(): void
    {
        // Set any quizzes with timer_enabled = false to true, unless they explicitly have a 0 duration
        // Only do this for quizzes that have a valid duration_minutes > 0
        DB::table('quizzes')
            ->where('timer_enabled', false)
            ->where('duration_minutes', '>', 0)
            ->update(['timer_enabled' => true]);

        // For quizzes without a duration, set to default 10 minutes and enable timer
        DB::table('quizzes')
            ->where('timer_enabled', false)
            ->whereNull('duration_minutes')
            ->update([
                'duration_minutes' => 10,
                'timer_enabled' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only fixes data, no rollback needed
        // The structure was already correct from previous migrations
    }
};
