<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_retake_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('additional_graded_attempts')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_retake_allowances');
    }
};
