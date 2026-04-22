<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('quiz_attempts', 'school_year')) {
                $table->string('school_year')->nullable()->after('attempt_type')->index();
            }
        });

        DB::table('quiz_attempts')
            ->select(['id', 'started_at', 'submitted_at', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($attempts): void {
                foreach ($attempts as $attempt) {
                    $referenceDate = $attempt->started_at ?? $attempt->submitted_at ?? $attempt->created_at;

                    if (! $referenceDate) {
                        continue;
                    }

                    $referenceDate = Carbon::parse($referenceDate);
                    $startYear = $referenceDate->month >= 6 ? $referenceDate->year : ($referenceDate->year - 1);

                    DB::table('quiz_attempts')
                        ->where('id', $attempt->id)
                        ->update([
                            'school_year' => sprintf('%d-%d', $startYear, $startYear + 1),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'school_year')) {
                $table->dropIndex(['school_year']);
                $table->dropColumn('school_year');
            }
        });
    }
};
