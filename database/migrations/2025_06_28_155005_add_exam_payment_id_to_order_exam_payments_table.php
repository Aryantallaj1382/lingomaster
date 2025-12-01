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
        Schema::table('OrderExamPayments', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_payment_id')->after('id'); // یا بعد از فیلد مناسب
            $table->foreign('exam_payment_id')
                ->references('id')->on('ExamPayments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('OrderExamPayments ', function (Blueprint $table) {
            //
        });
    }
};
