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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('unit_id')->comment('This will be from unit(id) table')->nullable();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->float('price',10,2)->nullable();
            $table->text('image_path')->nullable();
            $table->float('current_quanitity', 12, 4)->default(0);
            $table->float('alert_quanitity', 12, 4)->default(5);
            $table->boolean('status')->comment('1 for Active, 2 for Inactive')->nullable();
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
        Schema::dropIfExists('products');
    }
};
