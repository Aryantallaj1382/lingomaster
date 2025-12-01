<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsForStudentTable extends Migration
{
    public function up()
    {
        Schema::create('discounts_for_student', function (Blueprint $table) {
            $table->id();

            // polymorphic relation
            $table->morphs('discountable'); // creates discountable_id and discountable_type

            $table->string('code')->unique(); // کد تخفیف
            $table->unsignedTinyInteger('percentage'); // درصد تخفیف
            $table->unsignedInteger('limit')->nullable(); // مثلاً برای 10 نفر اول
            $table->date('expires_at')->nullable(); // تاریخ انقضا

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('discounts_for_student');
    }
}
