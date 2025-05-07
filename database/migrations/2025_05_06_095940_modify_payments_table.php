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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'gateway_ref', 'amount']); // remove unnecessary columns
            $table->unsignedBigInteger('transaction_id')->nullable()->after('ref_no');

            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn('transaction_id');

            // Optionally re-add the dropped fields if needed in rollback
            $table->string('payment_type')->nullable();
            $table->string('gateway_ref')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
        });
    }
};
