<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'academic_year')) {
                $table->string('academic_year')->nullable()->index()->after('role');
            }
        });

        DB::table('users')
            ->where('role', 'student')
            ->where('student_id', 'like', '23-%')
            ->update(['academic_year' => '2023-2024']);

        DB::table('users')
            ->where('role', 'student')
            ->where('student_id', 'like', '24-%')
            ->update(['academic_year' => '2024-2025']);

        DB::table('users')
            ->where('role', 'student')
            ->where('student_id', 'like', '25-%')
            ->update(['academic_year' => '2025-2026']);

        DB::table('users')
            ->where('role', 'teacher')
            ->where('email', 'like', '%.2023-2024@techquiz.edu')
            ->update(['academic_year' => '2023-2024']);

        DB::table('users')
            ->where('role', 'teacher')
            ->where('email', 'like', '%.2024-2025@techquiz.edu')
            ->update(['academic_year' => '2024-2025']);

        DB::table('users')
            ->where('role', 'teacher')
            ->where('email', 'like', '%.2025-2026@techquiz.edu')
            ->update(['academic_year' => '2025-2026']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'academic_year')) {
                $table->dropIndex(['academic_year']);
                $table->dropColumn('academic_year');
            }
        });
    }
};
