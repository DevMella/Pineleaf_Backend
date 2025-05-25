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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('email');
            $table->string('phone');
            $table->date('consultation_date');
            $table->time('consultation_time');
            $table->text('notes')->nullable();
            $table->integer('no_attendees')->default(1);
            $table->enum('mode', ['in-person', 'online'])->default('in-person');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};