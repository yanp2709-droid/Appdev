<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('attempt_answers', 'selected_option_ids')) {
                $table->json('selected_option_ids')->nullable()->after('question_option_id');
            }
        });

        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->enum('question_type', ['mcq', 'tf', 'multi_select', 'ordering', 'short_answer'])
                    ->default('mcq')
                    ->change();
            });
        } catch (Throwable $e) {
            // Fresh installs already receive the updated enum from the base migration.
        }
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (Schema::hasColumn('attempt_answers', 'selected_option_ids')) {
                $table->dropColumn('selected_option_ids');
            }
        });

        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->enum('question_type', ['mcq', 'tf', 'ordering', 'short_answer'])
                    ->default('mcq')
                    ->change();
            });
        } catch (Throwable $e) {
            // Ignore rollback enum changes when the database driver does not support them.
        }
    }
};
