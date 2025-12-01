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
        Schema::table('notifications', function (Blueprint $table) {
            // حذف foreign key قبل از حذف ستون
            $table->dropForeign(['professors_id']);
            // حالا حذف ستون
            $table->dropColumn('professors_id');

            // اضافه کردن ستون user_id
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            // اضافه کردن foreign key اگر نیاز هست
             $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_id', function (Blueprint $table) {
            //
        });
    }
};
