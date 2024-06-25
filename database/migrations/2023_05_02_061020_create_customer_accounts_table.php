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
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('previous_balance')->nullable();
            $table->integer('sale')->nullable();
            $table->integer('payment_received')->nullable();
            $table->integer('new_balance')->nullable();
            $table->boolean('mode_of_transaction')->comment('1 for cash, 2 for online')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
