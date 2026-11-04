<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EquipmentController extends Controller
{
    /**
     * Lista los equipos, paginados. Filtros opcionales: ?status= y ?type=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $equipment = Equipment::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('code')
            ->paginate(15);

        return EquipmentResource::collection($equipment);
    }

    /**
     * Crea un equipo.
     */
    public function store(StoreEquipmentRequest $request): EquipmentResource
    {
        $equipment = Equipment::create($request->validated());

        return new EquipmentResource($equipment->refresh());
    }

    /**
     * Muestra un equipo.
     */
    public function show(Equipment $equipment): EquipmentResource
    {
        return new EquipmentResource($equipment);
    }

    /**
     * Edita un equipo (se pueden enviar solo los campos que cambian).
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment): EquipmentResource
    {
        $equipment->update($request->validated());

        return new EquipmentResource($equipment);
    }

    /**
     * Elimina un equipo (soft delete: su historial se conserva).
     */
    public function destroy(Equipment $equipment): Response
    {
        $equipment->delete();

        return response()->noContent();
    }
}