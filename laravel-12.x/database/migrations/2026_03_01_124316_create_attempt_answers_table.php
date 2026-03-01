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
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            // Which quiz attempt this answer belongs to
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->onDelete('cascade');

            // Which question was answered
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');

            // Which answer student chose (can be nullable if free text)
            $table->foreignId('answer_id')->nullable()->constrained('answers')->onDelete('set null');

            // Optional: store text answer for Fill in the Blank or Picture
            $table->text('text_answer')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
