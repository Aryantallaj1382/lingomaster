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
        Schema::create('professor_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id');
            $table->unsignedBigInteger('professor_id');
            $table->boolean('webinar')->default(false);
            $table->boolean('workshop')->default(false);
            $table->boolean('private_class')->default(false);
            $table->boolean('group_class')->default(false);
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            $table->foreign('professor_id')->references('id')->on('professors')->onDelete('cascade');
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
