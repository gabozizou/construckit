<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceDueResource;
use App\Models\Equipment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceDueController extends Controller
{
    /**
     * Lista los equipos que necesitan mantención preventiva,
     * del más urgente al menos urgente.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $equipment = Equipment::needsPreventiveMaintenance()->get();

        return MaintenanceDueResource::collection($equipment);
    }
}