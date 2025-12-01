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
        Schema::table('report_registration', function (Blueprint $table) {
            $table->unsignedBigInteger('class_id')->nullable()->after('privet_teacher_time_slot');
            $table->foreign('class_id')
                ->references('id')
                ->on('private_class_reservations')
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_registration', function (Blueprint $table) {
            //
        });
    }
};
