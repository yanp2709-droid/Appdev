<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'total_items')) {
                $table->unsignedInteger('total_items')->default(0);
            }
            if (!Schema::hasColumn('quiz_attempts', 'answered_count')) {
                $table->unsignedInteger('answered_count')->default(0);
            }
            if (!Schema::hasColumn('quiz_attempts', 'correct_answers')) {
                $table->unsignedInteger('correct_answers')->default(0);
            }
            if (!Schema::hasColumn('quiz_attempts', 'score_percent')) {
                $table->decimal('score_percent', 5, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'score_percent')) {
                $table->dropColumn('score_percent');
            }
            if (Schema::hasColumn('quiz_attempts', 'correct_answers')) {
                $table->dropColumn('correct_answers');
            }
            if (Schema::hasColumn('quiz_attempts', 'answered_count')) {
                $table->dropColumn('answered_count');
            }
            if (Schema::hasColumn('quiz_attempts', 'total_items')) {
                $table->dropColumn('total_items');
            }
        });
    }
};
