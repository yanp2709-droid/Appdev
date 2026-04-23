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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('widget_class'); // Full class path
            $table->string('widget_name'); // Display name
            $table->integer('order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->json('settings')->nullable(); // Store widget-specific settings
            $table->timestamps();

            $table->unique(['user_id', 'widget_class']);
            $table->index(['user_id', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
