<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            if (!Schema::hasColumn('question_options', 'order_index')) {
                $table->integer('order_index')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            if (Schema::hasColumn('question_options', 'order_index')) {
                $table->dropColumn('order_index');
            }
        });
    }
};
