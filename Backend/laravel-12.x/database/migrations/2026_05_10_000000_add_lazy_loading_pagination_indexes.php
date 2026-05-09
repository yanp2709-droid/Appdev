<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Determine whether a named index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->contains($indexName);
    }

    /**
     * Run the migrations.
     * Adds indexes to optimize pagination queries for lazy loading.
     */
    public function up(): void
    {
        // Add indexes to categories table for pagination
        Schema::table('categories', function (Blueprint $table) {
            if (!$this->hasIndex('categories', 'idx_categories_published_created')) {
                $table->index(['is_published', 'created_at'], 'idx_categories_published_created');
            }
            if (!$this->hasIndex('categories', 'idx_categories_published_name')) {
                $table->index(['is_published', 'name'], 'idx_categories_published_name');
            }
        });

        // Add indexes to quizzes table for pagination
        Schema::table('quizzes', function (Blueprint $table) {
            if (!$this->hasIndex('quizzes', 'idx_quizzes_category_active_created')) {
                $table->index(['category_id', 'is_active', 'created_at'], 'idx_quizzes_category_active_created');
            }
            if (!$this->hasIndex('quizzes', 'idx_quizzes_category_created')) {
                $table->index(['category_id', 'created_at'], 'idx_quizzes_category_created');
            }
            if (!$this->hasIndex('quizzes', 'idx_quizzes_is_active_created')) {
                $table->index(['is_active', 'created_at'], 'idx_quizzes_is_active_created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_categories_published_created');
            $table->dropIndexIfExists('idx_categories_published_name');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_quizzes_category_active_created');
            $table->dropIndexIfExists('idx_quizzes_category_created');
            $table->dropIndexIfExists('idx_quizzes_is_active_created');
        });
    }
};
