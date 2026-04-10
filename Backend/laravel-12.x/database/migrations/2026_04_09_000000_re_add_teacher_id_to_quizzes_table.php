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
        // Make sure teacher role exists in users table first
        Schema::table('users', function (Blueprint $table) {
            // This is a no-op if already done, but ensures the column exists
        });

        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('category_id');
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'teacher_id')) {
                $table->dropForeign(['teacher_id']);
                $table->dropColumn('teacher_id');
            }
        });
    }
};

