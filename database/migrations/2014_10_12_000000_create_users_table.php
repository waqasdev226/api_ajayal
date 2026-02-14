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
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('reference')->nullable();
            $table->decimal('cash', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('min_ratio', 8, 2)->nullable();
            $table->decimal('max_ratio', 8, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->date('expire_contract')->nullable();
            $table->string('city')->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->string('wdr_method')->nullable();
            $table->string('wdr_phone')->nullable();
            $table->string('wdr_card_no')->nullable();
            $table->string('wdr_name')->nullable();
            $table->string('wdr_passport')->nullable();
            $table->string('wdr_bank_account')->nullable();
            $table->string('wdr_swift')->nullable();
            $table->string('document_type')->nullable();
            $table->string('id_front_image')->nullable();
            $table->string('id_back_image')->nullable();
            $table->rememberToken();
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
