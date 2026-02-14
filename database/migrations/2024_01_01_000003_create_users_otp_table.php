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
        Schema::create('users_otp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('otp');
            $table->string('reference')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('finish_at')->nullable();
            $table->string('type')->nullable(); // login, verify, reset_password, etc.
            $table->unsignedBigInteger('type_id')->nullable();
            $table->string('status')->default('pending'); // pending, used, expired

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_otp');
    }
};
