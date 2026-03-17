<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index on quiz_attempts for faster history queries
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'student_id') || !$this->hasIndex('quiz_attempts', 'idx_quiz_attempts_student_status')) {
                $table->index(['student_id', 'status', 'submitted_at'], 'idx_quiz_attempts_student_status');
            }
        });

        // Index on questions for faster quiz question queries
        Schema::table('questions', function (Blueprint $table) {
            if (!$this->hasIndex('questions', 'idx_questions_quiz_id')) {
                $table->index('quiz_id', 'idx_questions_quiz_id');
            }
        });

        // Index on attempt_answers for faster detail queries
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (!$this->hasIndex('attempt_answers', 'idx_attempt_answers_quiz_attempt')) {
                $table->index('quiz_attempt_id', 'idx_attempt_answers_quiz_attempt');
            }
            if (!$this->hasIndex('attempt_answers', 'idx_attempt_answers_question')) {
                $table->index('question_id', 'idx_attempt_answers_question');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_quiz_attempts_student_status');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_questions_quiz_id');
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_attempt_answers_quiz_attempt');
            $table->dropIndexIfExists('idx_attempt_answers_question');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name = ?",
            [$table]
        );

        return collect($indexes)->pluck('name')->contains($indexName);
    }
};
