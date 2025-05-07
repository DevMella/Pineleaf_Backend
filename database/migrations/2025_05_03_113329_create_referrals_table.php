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
        if (!Schema::hasTable('referrals')) {
         Schema::create('referrals', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('referre_id'); // The new user
        $table->unsignedBigInteger('referral_id'); // The one who referred
        $table->unsignedBigInteger('transaction_id')->nullable();
        $table->integer('level')->default(1);
        $table->decimal('bonus', 10, 2)->default(0.00);
        $table->timestamps();

        // Foreign key constraints (optional but good practice)
        $table->foreign('refeirre_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('referral_id')->references('id')->on('users')->onDelete('cascade');
    });
}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
