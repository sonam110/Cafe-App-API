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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('category_id')->comment('This will be from category(id) table')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            $table->unsignedBigInteger('unit_id')->comment('This will be from unit(id) table')->nullable();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->string('name', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->float('order_duration')->nullable();
            $table->integer('priority_rank')->nullable();
            $table->float('quantity')->nullable();            
            $table->float('price')->comment('This will be used to subtract from Expense')->nullable();
            $table->boolean('create_menu')->default(2)->nullable();
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
        Schema::dropIfExists('menus');
    }
};
