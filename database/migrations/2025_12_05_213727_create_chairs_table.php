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
        Schema::create('chairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beach_id')->constrained('beaches')->cascadeOnDelete();
            $table->string('code');
            $table->decimal('price', 10, 2);
            $table->enum('type', ['vip', 'adult', 'family']);
            $table->enum('status', ['available', 'reserved', 'maintenance'])->default('available');
            $table->string('column_letter');
            $table->integer('row_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chairs');
    }
};
