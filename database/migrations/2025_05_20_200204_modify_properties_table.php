<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use
    Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * *Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'location')) {
                $table->unsignedBigInteger('location')->after('landmark');

                $table->foreign('location')
                    ->references('id')
                    ->on('locations')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'location')) {
                $table->dropForeign(['location']);
                $table->dropColumn('location');
            }
        });
    }
};
