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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('item')->nullable();

            // $table->unsignedBigInteger('product_id')->nullable();
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // $table->unsignedBigInteger('unit_id')->nullable();
            // $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            // $table->string('quantity', 100)->nullable();
            // $table->string('price', 100)->nullable();
            $table->integer('total_expense')->nullable();
            $table->date('expense_date')->comment('This will be in yyyy-mm-dd')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('expenses');
    }
};
