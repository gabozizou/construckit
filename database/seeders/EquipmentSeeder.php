<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Services\HourmeterService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Flota de ejemplo con cargas y mantenciones de octubre de 2026.
 * Los datos son fijos (sin azar) para que el reporte siempre dé los mismos resultados.
 * Se registran con HourmeterService, así que cumplen la regla del horómetro.
 */
class EquipmentSeeder extends Seeder
{
    public function run(HourmeterService $hourmeter): void
    {
        $fleet = [
            // Preventiva reciente: NO aparece en "necesitan mantención".
            [
                'equipment' => ['code' => 'CAM-101', 'type' => 'camion', 'plate' => 'KTRZ21', 'brand' => 'Volvo', 'model' => 'FH16'],
                'start' => 1000, 'hours_per_load' => 40, 'liters_per_hour' => 2.3, 'preventive_after_load' => 6,
            ],
            // Última preventiva hace más de 250 h: SÍ aparece.
            [
                'equipment' => ['code' => 'EXC-201', 'type' => 'excavadora', 'plate' => null, 'brand' => 'Komatsu', 'model' => 'PC200'],
                'start' => 3200, 'hours_per_load' => 45, 'liters_per_hour' => 14.5, 'preventive_after_load' => 1,
            ],
            // Nunca tuvo preventiva y supera 250 h: SÍ aparece.
            [
                'equipment' => ['code' => 'GEN-301', 'type' => 'generador', 'plate' => null, 'brand' => 'Caterpillar', 'model' => 'C15'],
                'start' => 120, 'hours_per_load' => 30, 'liters_per_hour' => 8.0, 'preventive_after_load' => null,
            ],
        ];

        foreach ($fleet as $item) {
            $equipment = Equipment::create($item['equipment'] + ['current_hourmeter' => $item['start']]);

            $date = CarbonImmutable::parse('2026-10-01 08:00:00');
            $currentHourmeter = $item['start'];

            // 8 cargas, una cada 3 días.
            for ($load = 1; $load <= 8; $load++) {
                $liters = round($item['hours_per_load'] * $item['liters_per_hour'], 2);

                $hourmeter->registerFuelLoad($equipment, [
                    'loaded_at' => $date,
                    'liters' => $liters,
                    'amount_clp' => (int) round($liters * 1100), // ~$1.100 por litro de diésel
                    'hourmeter' => $currentHourmeter,
                ]);

                if ($load === $item['preventive_after_load']) {
                    $hourmeter->registerMaintenance($equipment, [
                        'type' => 'preventiva',
                        'performed_on' => $date->toDateString(),
                        'hourmeter' => $currentHourmeter,
                        'cost_clp' => 350000,
                        'description' => 'Mantención preventiva: aceite, filtros y revisión general.',
                    ]);
                }

                $currentHourmeter += $item['hours_per_load'];
                $date = $date->addDays(3);
            }
        }

        // Una correctiva, para que el reporte muestre gasto de mantención de ese tipo.
        $hourmeter->registerMaintenance(Equipment::where('code', 'CAM-101')->first(), [
            'type' => 'correctiva',
            'performed_on' => '2026-10-22',
            'hourmeter' => 1280,
            'cost_clp' => 180000,
            'description' => 'Reemplazo de manguera hidráulica.',
        ]);
    }
}