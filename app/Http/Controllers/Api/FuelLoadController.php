<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuelLoadRequest;
use App\Http\Resources\FuelLoadResource;
use App\Models\Equipment;
use App\Services\HourmeterService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FuelLoadController extends Controller
{
    public function __construct(private HourmeterService $hourmeter)
    {
    }

    /**
     * Lista las cargas de un equipo, de la más reciente a la más antigua.
     */
    public function index(Equipment $equipment): AnonymousResourceCollection
    {
        $loads = $equipment->fuelLoads()
            ->latest('loaded_at')
            ->paginate(15);

        return FuelLoadResource::collection($loads);
    }

    /**
     * Registra una carga y actualiza el horómetro del equipo si corresponde.
     */
    public function store(StoreFuelLoadRequest $request, Equipment $equipment): FuelLoadResource
    {
        $load = $this->hourmeter->registerFuelLoad($equipment, $request->validated());

        return new FuelLoadResource($load);
    }
}