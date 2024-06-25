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
        Schema::create('salary_management', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');

            $table->date('date');
            $table->float('total_salary',10,2);
            $table->float('previous_balance',10,2)->default(0);
            $table->float('calculated_salary',10,2)->default(0);
            $table->float('current_month_advance',10,2)->default(0);
            $table->float('new_balance',10,2)->nullable()->comment('previous_balance + calculated_salary - advance : will be calculated on month end');
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_management');
    }
};
