<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. survey_questions
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->enum('class_type', [
                'private_online', 'private_offline',
                'group_online', 'group_offline',
                'webinar', 'workshop', 'mock_test'
            ]);
            $table->string('question');
            $table->unsignedTinyInteger('order')->default(1);
            $table->timestamps();
        });

        // 2. survey_responses
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('surveyable_id');
            $table->string('surveyable_type');
            $table->timestamp('submitted_at')->nullable(); // null = هنوز تکمیل نشده
            $table->timestamps();

            $table->index(['surveyable_type', 'surveyable_id']);
        });

        Schema::create('survey_response_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained('survey_responses')->onDelete('cascade');
            $table->foreignId('survey_question_id')->constrained('survey_questions')->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); // 1 تا 5
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('survey_response_items');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_questions');
    }
};
