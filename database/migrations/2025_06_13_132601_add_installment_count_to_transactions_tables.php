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
          Schema::table('transactions', function (Blueprint $table) {
$table->string('meta')->nullable();
$table->string('installment_count')->nullable();
          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions_tables', function (Blueprint $table) {
            //
        });
    }
};
