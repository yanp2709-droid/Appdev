<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'quiz_id')) {
                $table->foreignId('quiz_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('quizzes')
                    ->nullOnDelete()
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'quiz_id')) {
                try {
                    $table->dropForeign(['quiz_id']);
                } catch (\Throwable $e) {
                    // Ignore missing FK during rollback.
                }

                $table->dropColumn('quiz_id');
            }
        });
    }
};
