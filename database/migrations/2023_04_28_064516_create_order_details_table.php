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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->unsignedBigInteger('menu_id')->nullable();
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');

            $table->text('menu_detail')->nullable();
            $table->integer('quantity')->nullable();
            $table->float('price',10,2)->nullable();
            $table->float('tax',10,2)->nullable()->comment('category percent on price');
            $table->float('sub_total',10,2)->nullable()->comment('(price + tax)* quantity');
            $table->decimal('total',10,2)->nullable()->comment('total amount');
            $table->decimal('discount_per',10,2)->nullable();
            $table->decimal('discount_amount',10,2)->nullable();
            $table->text('instructions')->nullable();
            $table->integer('preparation_duration')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
