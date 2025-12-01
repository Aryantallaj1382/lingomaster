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
        Schema::create('placement_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professor_id'); // استاد
            $table->unsignedBigInteger('language_id'); // زبان
            $table->unsignedBigInteger('age_group_id'); // گروه سنی
            $table->enum('price', ['free', '50_percent', 'no_discount'])->default('no_discount'); // وضعیت قیمت

            $table->timestamps();

            $table->foreign('professor_id')->references('id')->on('professors')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            $table->foreign('age_group_id')->references('id')->on('age_groups')->onDelete('cascade');
        });

        Schema::create('placement_test_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('placement_test_id');
            $table->unsignedBigInteger('skill_id');
            $table->timestamps();

            $table->foreign('placement_test_id')->references('id')->on('placement_tests')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
        Schema::table('private_class_reservations', function (Blueprint $table) {
            $table->foreignId('skill_id')
                ->nullable()
                ->after('id')             // می‌تونی جای مناسبش رو تعیین کنی
                ->constrained('skills')   // کلید خارجی به جدول skills
                ->nullOnDelete();         // اگر skill حذف شد، مقدار skill_id نال بشه
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
