<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'question_type')) {
                $table->enum('question_type', ['mcq', 'tf', 'multi_select', 'ordering', 'short_answer'])->default('mcq')->after('category_id');
            }
            if (!Schema::hasColumn('questions', 'question_text')) {
                $table->longText('question_text')->nullable()->after('question_type');
            }
            if (!Schema::hasColumn('questions', 'points')) {
                $table->integer('points')->default(5)->after('question_text');
            }
            if (!Schema::hasColumn('questions', 'answer_key')) {
                $table->longText('answer_key')->nullable()->after('points');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'question_type')) {
                $table->dropColumn('question_type');
            }
            if (Schema::hasColumn('questions', 'question_text')) {
                $table->dropColumn('question_text');
            }
            if (Schema::hasColumn('questions', 'points')) {
                $table->dropColumn('points');
            }
            if (Schema::hasColumn('questions', 'answer_key')) {
                $table->dropColumn('answer_key');
            }
        });
    }
};
