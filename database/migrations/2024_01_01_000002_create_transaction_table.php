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
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from')->nullable();
            $table->unsignedBigInteger('to')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('current_profit', 15, 2)->nullable();
            $table->string('type'); // deposit, withdraw, transfer, profit, etc.
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            $table->text('note')->nullable();
            $table->string('method')->nullable(); // cash, bank, card, etc.
            $table->timestamps();

            // Foreign keys
            $table->foreign('from')->references('id')->on('users')->onDelete('set null');
            $table->foreign('to')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
