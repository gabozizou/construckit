<?php

namespace App\Http\Resources;

use App\Models\Equipment;
use Illuminate\Http\Request;

/**
 * Equipo con mantención preventiva pendiente: los datos del equipo
 * mas el hormetro de su última preventiva y las horas transcurridas desde ella
 */
class MaintenanceDueResource extends EquipmentResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // null = el equipo nunca tuvo una mantención preventiva.
        $lastPreventive = $this->last_preventive_hourmeter !== null
            ? (float) $this->last_preventive_hourmeter
            : null;

        return array_merge(parent::toArray($request), [
            'last_preventive_hourmeter' => $lastPreventive,
            'hours_since_preventive' => round((float) $this->current_hourmeter - ($lastPreventive ?? 0), 1),
            'interval_hours' => Equipment::PREVENTIVE_INTERVAL_HOURS,
        ]);
    }
}