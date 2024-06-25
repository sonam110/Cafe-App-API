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
        Schema::table('stock_manages', function (Blueprint $table) {
            $table->string('shop_name')->after('resource')->nullable();
            $table->timestamp('date')->after('shop_name')->nullable();
            $table->string('bill_no')->after('date')->nullable();
            $table->text('address')->after('bill_no')->nullable();
            $table->string('purchase_by')->after('address')->nullable();
            $table->unsignedBigInteger('recieved_by')->after('purchase_by')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_manages', function (Blueprint $table) {
            //
        });
    }
};
