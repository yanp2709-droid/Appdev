<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_picture')->nullable();
            $table->string('position')->nullable();
            $table->json('subjects_teaching')->nullable();
            $table->string('it_specialization')->nullable();
            $table->text('educational_background')->nullable();
            $table->json('skills_technologies')->nullable();
            $table->json('certifications')->nullable();
            $table->integer('years_experience')->nullable();
            $table->text('professional_summary')->nullable();
            $table->string('contact_info')->nullable();
            $table->string('office_schedule')->nullable();
            $table->string('department')->nullable();
            $table->json('programming_languages')->nullable();
            $table->json('frameworks_tools')->nullable();
            $table->json('database_experience')->nullable();
            $table->json('software_expertise')->nullable();
            $table->text('research_interests')->nullable();
            $table->text('current_projects')->nullable();
            $table->json('achievements')->nullable();
            $table->json('portfolio_links')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_picture',
                'position',
                'subjects_teaching',
                'it_specialization',
                'educational_background',
                'skills_technologies',
                'certifications',
                'years_experience',
                'professional_summary',
                'contact_info',
                'office_schedule',
                'department',
                'programming_languages',
                'frameworks_tools',
                'database_experience',
                'software_expertise',
                'research_interests',
                'current_projects',
                'achievements',
                'portfolio_links',
            ]);
        });
    }
};
