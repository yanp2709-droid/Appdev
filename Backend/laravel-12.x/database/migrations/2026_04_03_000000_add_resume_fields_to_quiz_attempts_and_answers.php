<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'question_sequence')) {
                $table->json('question_sequence')->nullable()->after('score_percent');
            }
            if (!Schema::hasColumn('quiz_attempts', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->index()->after('question_sequence');
            }
            if (!Schema::hasColumn('quiz_attempts', 'last_viewed_question_id')) {
                $table->foreignId('last_viewed_question_id')
                    ->nullable()
                    ->after('last_activity_at')
                    ->constrained('questions')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('quiz_attempts', 'last_viewed_question_index')) {
                $table->unsignedInteger('last_viewed_question_index')->nullable()->after('last_viewed_question_id');
            }
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('attempt_answers', 'is_bookmarked')) {
                $table->boolean('is_bookmarked')->default(false)->after('is_correct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (Schema::hasColumn('attempt_answers', 'is_bookmarked')) {
                $table->dropColumn('is_bookmarked');
            }
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'last_viewed_question_index')) {
                $table->dropColumn('last_viewed_question_index');
            }
            if (Schema::hasColumn('quiz_attempts', 'last_viewed_question_id')) {
                $table->dropConstrainedForeignId('last_viewed_question_id');
            }
            if (Schema::hasColumn('quiz_attempts', 'last_activity_at')) {
                $table->dropColumn('last_activity_at');
            }
            if (Schema::hasColumn('quiz_attempts', 'question_sequence')) {
                $table->dropColumn('question_sequence');
            }
        });
    }
};
