<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Resources\MaintenanceResource;
use App\Models\Equipment;
use App\Services\HourmeterService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceController extends Controller
{
    public function __construct(private HourmeterService $hourmeter)
    {
    }

    /**
     * Lista las mantenciones de un equipo, de la más reciente a la más antigua.
     */
    public function index(Equipment $equipment): AnonymousResourceCollection
    {
        $maintenances = $equipment->maintenances()
            ->latest('performed_on')
            ->paginate(15);

        return MaintenanceResource::collection($maintenances);
    }

    /**
     * Registra una mantención y actualiza el horómetro del equipo si corresponde.
     */
    public function store(StoreMaintenanceRequest $request, Equipment $equipment): MaintenanceResource
    {
        $maintenance = $this->hourmeter->registerMaintenance($equipment, $request->validated());

        return new MaintenanceResource($maintenance);
    }
}