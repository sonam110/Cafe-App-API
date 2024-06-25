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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('customer_id')->comment('This will be users table')->nullable();
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            // $table->string('name');
            // $table->string('email')->nullable();
            // $table->integer('mobile');
            $table->string('order_number');
            $table->integer('table_number')->nullable();
            $table->integer('order_status')->default('1')->comment('1:Pending,2:confirmed,3:Completed,4:cancelled')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->integer('order_type')->default('1')->comment('1:Serve,2:Parcel')->nullable();
            $table->integer('payment_mode')->default('1')->comment('1:Cash,2:Online,3:Udhari,4:split')->nullable();
            $table->float('total_amount',10,2)->nullable();
            $table->integer('total_quantity')->nullable();
            $table->float('discount',10,2)->nullable();
            $table->float('tax_amount',10,2)->nullable();
            $table->float('payable_amount',10,2)->nullable();
            $table->float('cash_amount',10,2)->nullable();
            $table->float('online_amount',10,2)->nullable();
            $table->float('udhaar_amount',10,2)->nullable();
            $table->integer('order_duration')->nullable();  
            $table->string('invoice_path')->nullable();
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
        Schema::dropIfExists('orders');
    }
};
