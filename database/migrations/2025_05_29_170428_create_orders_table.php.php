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

        // migration: create_orders_table.php

        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained()->onDelete('cascade');

            $table->enum('status', ['pending', 'paid', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');

            $table->enum('seller_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('seller_rejection_reason')->nullable(); // دلیل رد سفارش توسط فروشنده

            $table->unsignedBigInteger('total_price')->default(0);
            $table->unsignedBigInteger('final_price')->default(0);

            $table->text('address')->nullable();
            $table->string('tracking_code')->nullable();
            $table->string('order_code')->nullable();

            $table->timestamps();
        });



        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price'); // قیمت محصول موقع سفارش
            $table->unsignedBigInteger('total_price'); // quantity * unit_price
            $table->timestamps();
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
