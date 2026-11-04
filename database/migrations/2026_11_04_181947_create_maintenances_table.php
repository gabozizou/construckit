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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')
                ->constrained('equipment')
                ->restrictOnDelete();
            $table->enum('type', ['preventiva', 'correctiva']);
            $table->date('performed_on');
            $table->decimal('hourmeter', 10, 1);
            $table->unsignedInteger('cost_clp');
            $table->text('description');
            $table->timestamps();

            $table->index(['equipment_id', 'performed_on']);
            $table->index(['equipment_id', 'type', 'hourmeter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
