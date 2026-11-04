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
        Schema::create('fuel_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')
                ->constrained('equipment')
                ->restrictOnDelete();
            $table->dateTime('loaded_at');
            $table->decimal('liters', 10, 2);
            $table->unsignedInteger('amount_clp');
            $table->decimal('hourmeter', 10, 1);
            $table->timestamps();

            $table->index(['equipment_id', 'loaded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_loads');
    }
};
