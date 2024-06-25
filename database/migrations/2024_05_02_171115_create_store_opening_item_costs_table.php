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
        Schema::create('store_opening_item_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('unit_id')->nullable();
            
            $table->string('item_name')->nullable();
            $table->float('quantity', 12, 4)->nullable();
            $table->float('price', 8, 2)->nullable();
            $table->string('shop_name')->nullable();
            $table->timestamp('date')->nullable();
            $table->string('bill_no')->nullable();
            $table->text('address')->nullable();
            $table->string('purchase_by')->nullable();
            $table->unsignedBigInteger('recieved_by')->nullable();
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
        Schema::dropIfExists('store_opening_item_costs');
    }
};
