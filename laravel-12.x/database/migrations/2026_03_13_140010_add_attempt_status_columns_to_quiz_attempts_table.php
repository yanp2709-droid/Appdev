<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'status')) {
                $table->string('status')->default('in_progress')->index();
            }
            if (!Schema::hasColumn('quiz_attempts', 'started_at')) {
                $table->timestamp('started_at')->nullable()->index();
            }
            if (!Schema::hasColumn('quiz_attempts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index();
            }
            if (!Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('quiz_attempts', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('quiz_attempts', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('quiz_attempts', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
