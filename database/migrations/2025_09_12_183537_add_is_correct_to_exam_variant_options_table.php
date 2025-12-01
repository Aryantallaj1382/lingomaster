<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('exam_variant_options');
        Schema::dropIfExists('exam_question_media');
        Schema::dropIfExists('exam_part_media');
        Schema::dropIfExists('exam_question_type');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_answer_options');
        Schema::dropIfExists('exam_variant_option');
        Schema::dropIfExists('exam_variant');
        Schema::dropIfExists('exam_question');
        Schema::dropIfExists('exam_parts');
        Schema::dropIfExists('exam_part_types');
        Schema::dropIfExists('exams');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_variant_options');
    }
};
