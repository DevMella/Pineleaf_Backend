<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Rename 'name' to 'fullName'
        $table->renameColumn('name', 'fullName');

        // Add new columns if they don't exist yet
        $table->string('number')->nullable();
        $table->string('referral_code')->nullable();
        $table->string('userName')->nullable()->unique(); // adjust 'unique' as needed
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->renameColumn('fullName', 'name');
        $table->dropColumn(['number', 'referral_code', 'userName']);
    });
}

};
