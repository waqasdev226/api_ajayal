<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaction')) {
            return;
        }
        Schema::table('transaction', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction', 'currency')) {
                $table->string('currency', 10)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('transaction', 'created_by_guard')) {
                $table->string('created_by_guard', 20)->nullable()->after('method');
            }
            if (!Schema::hasColumn('transaction', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('created_by_guard');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transaction')) {
            return;
        }
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn(['currency', 'created_by_guard', 'created_by_id']);
        });
    }
};
