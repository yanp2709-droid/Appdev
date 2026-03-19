<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('attempt_answers', 'is_correct')) {
                $table->boolean('is_correct')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (Schema::hasColumn('attempt_answers', 'is_correct')) {
                $table->dropColumn('is_correct');
            }
        });
    }
};
