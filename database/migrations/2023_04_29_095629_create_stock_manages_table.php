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
        Schema::create('stock_manages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('transfer_cafe_id')->nullable();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('unit_id')->nullable();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->unsignedBigInteger('menu_id')->nullable();
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');

            $table->unsignedBigInteger('expense_id')->nullable();
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');

            $table->float('quantity', 12, 4)->nullable();
            $table->float('price', 8, 2)->nullable();
            $table->enum('stock_operation', ['Out', 'In']);
            $table->enum('resource', ['Seller','Kitchen','Warehouse','Waste','Transfer'])->comment('Seller:stock-in from out side cafe,Kitchen:stock-out for order,Warehouse:stock-out to prepare product which will be selled in cafe,Waste:stock-out when products out of use,Transfer:Stock transfer(in/out)in to parent and child store');
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('stock_manages');
    }
};
