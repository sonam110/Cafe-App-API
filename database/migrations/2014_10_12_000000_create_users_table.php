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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();

            $table->unsignedBigInteger('cafe_id')->nullable();
            $table->foreign('cafe_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('is_parent')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->integer('role_id')->comment('1 for admin, 2 for cafe, 3 for admin-employee, 4 for employee,5 for customer,6 for SubCafe')->nullable();
            $table->bigInteger('mobile')->nullable();
            $table->text('address')->nullable();
            $table->boolean('gender')->comment('1 for male, 2 for female')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->boolean('status')->comment('1 for Active, 2 for Inactive')->default(1);

            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->boolean('subscription_status')->comment('1 for Active, 2 for Inactive')->nullable();

            $table->string('designation', 100)->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->date('joining_date')->comment('This will be in yyyy-mm-dd')->nullable();
            $table->date('birth_date')->comment('This will be in yyyy-mm-dd')->nullable();
            $table->integer('salary')->default(0)->nullable();
            $table->integer('salary_balance')->comment('Its initial value will be equal to salar')->nullable();
            $table->integer('account_balance')->nullable();
            $table->integer('no_of_subcafe')->default(0)->nullable();
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
        Schema::dropIfExists('users');
    }
};
