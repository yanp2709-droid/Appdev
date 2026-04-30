<?php

use App\Support\SchoolYears;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'school_year')) {
                $table->string('school_year')->nullable()->after('course')->index();
            }
        });

        if (! Schema::hasColumn('quiz_attempts', 'school_year')) {
            DB::table('users')
                ->where('role', 'student')
                ->whereNull('school_year')
                ->update(['school_year' => SchoolYears::current()]);

            return;
        }

        $students = DB::table('users')
            ->select('id')
            ->where('role', 'student')
            ->whereNull('school_year')
            ->get();

        foreach ($students as $student) {
            $latestSchoolYear = DB::table('quiz_attempts')
                ->where('student_id', $student->id)
                ->whereNotNull('school_year')
                ->where('school_year', '!=', '')
                ->orderByDesc('created_at')
                ->value('school_year');

            DB::table('users')
                ->where('id', $student->id)
                ->update([
                    'school_year' => $latestSchoolYear ?: SchoolYears::current(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'school_year')) {
                $table->dropIndex(['school_year']);
                $table->dropColumn('school_year');
            }
        });
    }
};
