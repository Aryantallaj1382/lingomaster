<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_returned')->default(false);
            $table->text('return_reason')->nullable();
            $table->string('return_video_path')->nullable();
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
