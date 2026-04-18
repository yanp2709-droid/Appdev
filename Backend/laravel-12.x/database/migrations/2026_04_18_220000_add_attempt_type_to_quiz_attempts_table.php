<?php

use App\Models\Quiz_attempt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('attempt_type')
                ->default(Quiz_attempt::TYPE_GRADED)
                ->after('quiz_id');
            $table->index(['student_id', 'quiz_id', 'attempt_type'], 'quiz_attempts_student_quiz_type_idx');
        });

        DB::table('quiz_attempts')
            ->whereNull('attempt_type')
            ->update(['attempt_type' => Quiz_attempt::TYPE_GRADED]);
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_student_quiz_type_idx');
            $table->dropColumn('attempt_type');
        });
    }
};
