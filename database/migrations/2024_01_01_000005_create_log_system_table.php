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
        Schema::create('log_system', function (Blueprint $table) {
            $table->id();
            $table->string('action')->nullable(); // create, update, delete, view, etc.
            $table->text('url_request')->nullable();
            $table->string('entity_name')->nullable(); // model name
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description')->nullable();
            $table->json('pre_data')->nullable(); // data before change
            $table->json('post_data')->nullable(); // data after change
            $table->string('host_address')->nullable(); // IP address
            $table->string('host_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_system');
    }
};
