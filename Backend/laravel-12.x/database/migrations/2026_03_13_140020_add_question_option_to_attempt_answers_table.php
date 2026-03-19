<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('attempt_answers', 'question_option_id')) {
                $table->foreignId('question_option_id')
                    ->nullable()
                    ->constrained('question_options')
                    ->nullOnDelete();
            }

            $table->unique(['quiz_attempt_id', 'question_id'], 'attempt_answers_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropUnique('attempt_answers_attempt_question_unique');
            if (Schema::hasColumn('attempt_answers', 'question_option_id')) {
                $table->dropConstrainedForeignId('question_option_id');
            }
        });
    }
};
