<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();

            // اطلاعات شخصی
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('degree')->nullable(); // کاردانی، کارشناسی، ارشد، دکتری
            $table->string('university_name')->nullable();
            $table->string('graduation_date')->nullable();

            // رشته تحصیلی
            $table->string('field_of_study')->nullable();
            $table->boolean('english_teaching')->nullable();
            $table->boolean('english_literature')->nullable();
            $table->boolean('english_translation')->nullable();
            $table->string('other_fields')->nullable();

            // سابقه تدریس آنلاین

            $table->json('online_teaching')->nullable(); // Zoom, Skype, ...

            // چالش‌ها
            $table->text('online_challenges')->nullable();
            $table->text('offline_experience')->nullable();

            // همکاری فعلی
            $table->boolean('is_cooperating_now')->default(false);
            $table->string('cooperation_platform')->nullable();
            $table->text('cooperation_details')->nullable();

            // حقوق و نوع همکاری
            $table->string('expected_salary')->nullable();
            $table->boolean('is_fulltime')->default(false);

            // ساعت تدریس (json: ["morning","afternoon"])
            $table->json('teaching_hours')->nullable();

            // گروه سنی
            $table->json('age_groups')->nullable(); // کودک، نوجوان، بزرگسال

            // سطح تدریس
            $table->json('teaching_levels')->nullable(); // مبتدی، متوسط، ...

            // نوع کلاس
            $table->json('class_types')->nullable(); // گروهی، وبینار، خصوصی

            // منابع
            $table->text('books_taught')->nullable();

            // TTC
            $table->text('ttc_experience')->nullable(); // توضیحات شرکت در TTC
            $table->boolean('is_willing_ttc')->default(false);
            $table->json('ttc_type')->nullable(); // آنلاین / حضوری

            // شروع همکاری
            $table->string('available_from')->nullable();

            // اهداف
            $table->text('short_term_goals')->nullable();
            $table->text('mid_term_goals')->nullable();
            $table->text('long_term_goals')->nullable();

            // نقاط ضعف و قوت
            $table->text('weaknesses')->nullable();
            $table->text('strengths')->nullable();

            // توانایی تدریس
            $table->boolean('can_teach_general')->default(false);
            $table->boolean('can_teach_specialized')->default(false);

            // تمایل به ارتقا
            $table->boolean('wants_improvement')->default(false);
            $table->string('code')->nullable();

            $table->timestamps();
        });

    }

    public function down(): void
    {
        //
    }
};
