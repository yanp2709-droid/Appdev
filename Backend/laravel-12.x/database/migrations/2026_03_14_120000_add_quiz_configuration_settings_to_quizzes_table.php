<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'timer_enabled')) {
                $table->boolean('timer_enabled')->default(true);
            }
            if (!Schema::hasColumn('quizzes', 'shuffle_questions')) {
                $table->boolean('shuffle_questions')->default(false);
            }
            if (!Schema::hasColumn('quizzes', 'shuffle_options')) {
                $table->boolean('shuffle_options')->default(false);
            }
            if (!Schema::hasColumn('quizzes', 'max_attempts')) {
                $table->unsignedInteger('max_attempts')->nullable();
            }
            if (!Schema::hasColumn('quizzes', 'show_score_immediately')) {
                $table->boolean('show_score_immediately')->default(true);
            }
            if (!Schema::hasColumn('quizzes', 'show_answers_after_submit')) {
                $table->boolean('show_answers_after_submit')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'timer_enabled')) {
                $table->dropColumn('timer_enabled');
            }
            if (Schema::hasColumn('quizzes', 'shuffle_questions')) {
                $table->dropColumn('shuffle_questions');
            }
            if (Schema::hasColumn('quizzes', 'shuffle_options')) {
                $table->dropColumn('shuffle_options');
            }
            if (Schema::hasColumn('quizzes', 'max_attempts')) {
                $table->dropColumn('max_attempts');
            }
            if (Schema::hasColumn('quizzes', 'show_score_immediately')) {
                $table->dropColumn('show_score_immediately');
            }
            if (Schema::hasColumn('quizzes', 'show_answers_after_submit')) {
                $table->dropColumn('show_answers_after_submit');
            }
        });
    }
};
