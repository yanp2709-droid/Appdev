<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'allow_review_before_submit')) {
                $table->boolean('allow_review_before_submit')->default(false)->after('max_attempts');
            }

            if (!Schema::hasColumn('quizzes', 'show_correct_answers_after_submit')) {
                $table->boolean('show_correct_answers_after_submit')->default(false)->after('show_answers_after_submit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'allow_review_before_submit')) {
                $table->dropColumn('allow_review_before_submit');
            }

            if (Schema::hasColumn('quizzes', 'show_correct_answers_after_submit')) {
                $table->dropColumn('show_correct_answers_after_submit');
            }
        });
    }
};
