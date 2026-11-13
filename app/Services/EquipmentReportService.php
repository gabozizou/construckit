<?php

namespace App\Services;

use App\Models\Equipment;
use Carbon\CarbonImmutable;

class EquipmentReportService
{
    public function generate(Equipment $equipment, CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Cargas del período (el día "to" se incluye completo, hasta las 23:59:59)
        $fuelQuery = $equipment->fuelLoads()
            ->whereBetween('loaded_at', [$from->startOfDay(), $to->endOfDay()]);

        // Totales calculados por MySQL en una sola consulta
        $fuel = (clone $fuelQuery)
            ->toBase()
            ->selectRaw('COUNT(*) AS loads_count')
            ->selectRaw('COALESCE(SUM(liters), 0) AS total_liters')
            ->selectRaw('COALESCE(SUM(amount_clp), 0) AS total_cost')
            ->selectRaw('MIN(hourmeter) AS first_hourmeter')
            ->selectRaw('MAX(hourmeter) AS last_hourmeter')
            ->first();

        // Litros de la primera carga del período (no cuentan para el rendimiento)
        $firstLoadLiters = (float) (clone $fuelQuery)
            ->orderBy('loaded_at')
            ->orderBy('id')
            ->value('liters');

        $maintenance = $equipment->maintenances()
            ->whereBetween('performed_on', [$from->toDateString(), $to->toDateString()])
            ->toBase()
            ->selectRaw('COUNT(*) AS maintenance_count')
            ->selectRaw('COALESCE(SUM(cost_clp), 0) AS total_cost')
            ->first();

        $performance = $this->performance($fuel, $firstLoadLiters);
        
        return [
            'equipment' => [
                'id' => $equipment->id,
                'code' => $equipment->code,
            ],
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'fuel' => [
                'loads_count' => (int) $fuel->loads_count,
                'total_liters' => round((float) $fuel->total_liters, 2),
                'total_cost_clp' => (int) $fuel->total_cost,
            ],
            'maintenance' => [
                'count' => (int) $maintenance->maintenance_count,
                'total_cost_clp' => (int) $maintenance->total_cost,
            ],
            'performance' => $performance,
            'cost_per_hour' => $this->costPerHour($fuel, $maintenance, $performance['hours_used'])
        ];
    }

    // El operador quiere saber cuanto le cuesta cada hora de un uso de un equipo
    // Agrega al reporte un campo con el gasto total (combustible + mantenciones) dividido por las horas de uso
    private function costPerHour(object $fuel, object $maintenance, ?float $hoursUsed): array
    {
        $totalCost = (int) $fuel->total_cost + (int) $maintenance->total_cost;
        return [
            'total_cost_clp' => $totalCost,
            'hours_used' => $hoursUsed,
            'cost_per_hour_clp' => $hoursUsed > 0 ? (int) round($totalCost / $hoursUsed) : null,
        ];
    }

    /**
     * Rendimiento en litros por hora, método "estanque lleno a estanque lleno":
     * los litros de la primera carga reponen consumo anterior al período, por eso no se cuentan.
     */
    private function performance(object $fuel, float $firstLoadLiters): array
    {
        if ((int) $fuel->loads_count < 2) {
            return [
                'hours_used' => null,
                'liters_per_hour' => null,
                'note' => 'Se necesitan al menos dos cargas en el período para calcular el rendimiento.',
            ];
        }

        $hoursUsed = round((float) $fuel->last_hourmeter - (float) $fuel->first_hourmeter, 1);

        if ($hoursUsed <= 0) {
            return [
                'hours_used' => 0.0,
                'liters_per_hour' => null,
                'note' => 'El horómetro no avanzó entre la primera y la última carga del período.',
            ];
        }

        $litersConsumed = (float) $fuel->total_liters - $firstLoadLiters;

        return [
            'hours_used' => $hoursUsed,
            'liters_per_hour' => round($litersConsumed / $hoursUsed, 2),
            'note' => null,
        ];
    }
}