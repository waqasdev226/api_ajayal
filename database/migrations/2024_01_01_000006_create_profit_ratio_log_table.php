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
        Schema::create('profit_ratio_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('cash', 15, 2)->default(0);
            $table->decimal('ratio', 8, 2)->default(0); // percentage
            $table->decimal('ratio_per_day', 8, 2)->default(0);
            $table->integer('days_to_calculate')->default(0);
            $table->decimal('total', 15, 2)->default(0); // calculated profit
            $table->string('status')->default('pending'); // pending, applied, cancelled
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
        Schema::dropIfExists('profit_ratio_log');
    }
};
