<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) exam_part_types
        Schema::create('exam_part_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();

            $table->unique('name', 'exam_part_types_name_unique');
        });

        // 2) exams
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('expiration')->nullable();
            $table->string('number_of_attempts')->nullable();
            $table->string('number_of_sections')->nullable();
            $table->string('description')->nullable();
            $table->string('name')->nullable();
            $table->string('view')->nullable();
            $table->timestamps();
            $table->dateTime('duration')->nullable();
            $table->string('type')->default('mock');
            // foreign keys (nullable ones use nullOnDelete as in your SQL)
            $table->foreignId('age_group_id')->nullable()->constrained('age_groups')->nullOnDelete();
            $table->foreignId('language_level_id')->nullable()->constrained('language_levels')->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();

            // books_id was present in your SQL as NOT NULL (no explicit FK constraint shown).
            // Here I assume there's a books table — if you don't have it, change/remove constrained() accordingly.
            $table->foreignId('books_id')->constrained('products')->cascadeOnDelete();
        });

        // 3) exam_parts (depends on exams and exam_part_types)
        Schema::create('exam_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('exam_part_type_id')->constrained('exam_part_types')->cascadeOnDelete();
            $table->integer('number');
            $table->string('title')->nullable();
            $table->text('text')->nullable();
            $table->text('passenger')->nullable();
            $table->string('passenger_title')->nullable();
            $table->string('question_title')->nullable();
            $table->timestamps();
        });

        // 4) exam_part_media (depends on exam_parts)
        Schema::create('exam_part_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_part_id')->constrained('exam_parts')->cascadeOnDelete();
            // original SQL used varchar(2048)
            $table->string('path', 2048)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 5) exam_questions (depends on exam_parts and exams)
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->foreignId('exam_part_id')->nullable()->constrained('exam_parts')->cascadeOnDelete();
            // note: original column name was exams_id (plural) — we'll keep the same name to match SQL
            $table->foreignId('exams_id')->nullable()->constrained('exams')->cascadeOnDelete();
            $table->enum('question_type', ['blank', 'test', 'speaking', 'writing']);
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('question')->nullable();
            $table->string('user_answer')->nullable();
            $table->timestamps();
        });

        // 6) exam_question_type (depends on exam_questions)
        Schema::create('exam_question_type', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('exam_question_id')->nullable()->constrained('exam_questions')->cascadeOnDelete();
            $table->timestamps();
        });

        // 7) exam_question_media (depends on exam_questions)
        Schema::create('exam_question_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_question_id')->nullable()->constrained('exam_questions')->cascadeOnDelete();
            $table->string('path')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

Schema::dropIfExists('exam_variants');
Schema::create('exam_variants', function (Blueprint $table) {
            $table->id();
            $table->string('text')->nullable();
            $table->timestamps();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
        });

        // 9) exam_variant_options (depends on exam_variants)
        Schema::create('exam_variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_variant_id')->nullable()->constrained('exam_variants')->cascadeOnDelete();
            $table->string('text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // 10) exam_answers (depends on exam_questions, exam_variants, users)
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_variant_id')->nullable()->constrained('exam_variants')->cascadeOnDelete();
            $table->text('text_answer')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
        });

        // 11) exam_answer_options (depends on exam_answers, exam_variants, exam_variant_options)
        Schema::create('exam_answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_answer_id')->constrained('exam_answers')->cascadeOnDelete();
            $table->foreignId('exam_variant_id')->constrained('exam_variants')->cascadeOnDelete();
            $table->foreignId('exam_variant_option_id')->constrained('exam_variant_options')->cascadeOnDelete();
            $table->timestamps();
        });

        // 12) exam_student (depends on exams and users)
        Schema::create('exam_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        // 13) exam_student_results (depends on exam_student)
        Schema::create('exam_student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_student_id')->constrained('exam_student')->cascadeOnDelete();
            $table->string('status');
            // default current_timestamp and on update current_timestamp
            $table->timestamp('date')->useCurrent()->useCurrentOnUpdate();
            $table->string('score');
            $table->string('reading');
            $table->string('listening');
            $table->string('writing');
            $table->string('speaking');
            $table->string('file');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // drop in reverse order to respect foreign key constraints
        Schema::dropIfExists('exam_student_results');
        Schema::dropIfExists('exam_student');
        Schema::dropIfExists('exam_answer_options');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_variant_options');
        Schema::dropIfExists('exam_variants');
        Schema::dropIfExists('exam_question_media');
        Schema::dropIfExists('exam_question_type');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exam_part_media');
        Schema::dropIfExists('exam_parts');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_part_types');
    }
};
