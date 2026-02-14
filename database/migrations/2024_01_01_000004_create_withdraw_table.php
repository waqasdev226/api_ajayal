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
        Schema::create('withdraw', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            $table->text('note')->nullable();
            $table->string('method')->nullable(); // bank, card, cash, etc.
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->string('passport')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('swift')->nullable();
            $table->string('card_no')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraw');
    }
};
