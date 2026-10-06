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
        Schema::create('tide_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beach_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('high_tide_time')->nullable();
            $table->time('low_tide_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tide_schedules');
    }
};
