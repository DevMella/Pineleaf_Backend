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
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id'); // Reference to the transaction
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamps();

            // Foreign key to transaction table
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
