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
        if (!Schema::hasTable('properties')) {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('estate_name');
            $table->text('description');
            $table->json('images')->nullable();
            $table->string('location');
            $table->json('landmark')->nullable();
            $table->string('size');
            $table->string('land_condition');
            $table->string('document_title');
            $table->string('property_features');
            $table->enum('type', ['land', 'house']);
            $table->enum('purpose', ['residential', 'commercial', 'mixed_use']);
            $table->decimal('price', 15, 2);
            $table->integer('total_units');
            $table->integer('unit_sold')->default(0);
            $table->string('flyer')->nullable();
            $table->timestamps();
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
