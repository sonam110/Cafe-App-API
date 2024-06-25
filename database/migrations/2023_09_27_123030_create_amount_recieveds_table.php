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
        Schema::create('amount_recieveds', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->foreign('subscription_id')->references('id')->on('cafe_subscriptions')->onDelete('cascade');

            $table->unsignedBigInteger('recieved_by')->nullable();
            $table->foreign('recieved_by')->references('id')->on('users')->onDelete('cascade');

            $table->integer('amount_recieved')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amount_recieveds');
    }
};
