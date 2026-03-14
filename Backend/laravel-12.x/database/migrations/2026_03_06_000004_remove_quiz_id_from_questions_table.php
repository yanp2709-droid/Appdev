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
            // Drop the foreign key constraint if it exists
            if (Schema::hasColumn('questions', 'quiz_id')) {
                try {
                    $table->dropForeign(['quiz_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('quiz_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Restore the quiz_id column if needed during rollback
            if (!Schema::hasColumn('questions', 'quiz_id')) {
                $table->foreignId('quiz_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            }
        });
    }
};
