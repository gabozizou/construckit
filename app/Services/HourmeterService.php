<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\FuelLoad;
use App\Models\Maintenance;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HourmeterService
{
    public function registerFuelLoad(Equipment $equipment, array $data): FuelLoad
    {
        return $this->withHourmeterCheck(
            $equipment,
            (float) $data['hourmeter'],
            fn (Equipment $locked) => $locked->fuelLoads()->create($data),
        );
    }

    public function registerMaintenance(Equipment $equipment, array $data): Maintenance
    {
        return $this->withHourmeterCheck(
            $equipment,
            (float) $data['hourmeter'],
            fn (Equipment $locked) => $locked->maintenances()->create($data),
        );
    }

    /**
     * Valida el horómetro contra el último registrado, crea el registro
     * y actualiza el horómetro del equipo, todo en una sola transacción.
     */
    private function withHourmeterCheck(Equipment $equipment, float $hourmeter, Closure $create): mixed
    {
        return DB::transaction(function () use ($equipment, $hourmeter, $create) {
            // Bloquea la fila del equipo hasta terminar la transacción,
            // para que dos registros simultáneos no lean el mismo horómetro.
            $locked = Equipment::whereKey($equipment->id)->lockForUpdate()->firstOrFail();

            $current = round((float) $locked->current_hourmeter, 1);
            $hourmeter = round($hourmeter, 1);

            if ($hourmeter < $current) {
                throw ValidationException::withMessages([
                    'hourmeter' => "El horómetro ({$hourmeter}) no puede ser menor al último registrado ({$current}).",
                ]);
            }

            $record = $create($locked);

            if ($hourmeter > $current) {
                $locked->update(['current_hourmeter' => $hourmeter]);
            }

            return $record;
        });
    }
}