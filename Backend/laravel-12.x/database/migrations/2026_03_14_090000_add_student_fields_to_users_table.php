<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id')->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'section')) {
                $table->string('section')->nullable();
            }
            if (!Schema::hasColumn('users', 'year_level')) {
                $table->string('year_level')->nullable();
            }
            if (!Schema::hasColumn('users', 'course')) {
                $table->string('course')->nullable();
            }
            if (!Schema::hasColumn('users', 'privacy_consent')) {
                $table->boolean('privacy_consent')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'privacy_consent')) {
                $table->dropColumn('privacy_consent');
            }
            if (Schema::hasColumn('users', 'course')) {
                $table->dropColumn('course');
            }
            if (Schema::hasColumn('users', 'year_level')) {
                $table->dropColumn('year_level');
            }
            if (Schema::hasColumn('users', 'section')) {
                $table->dropColumn('section');
            }
            if (Schema::hasColumn('users', 'student_id')) {
                $table->dropUnique(['student_id']);
                $table->dropColumn('student_id');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};
