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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 50);
            $table->string('plate', 20)->nullable();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->decimal('current_hourmeter', 10, 1)->default(0);
            $table->enum('status', ['operativo', 'en_mantencion', 'fuera_de_servicio'])
                ->default('operativo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
