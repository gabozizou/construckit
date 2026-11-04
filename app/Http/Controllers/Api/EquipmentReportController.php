<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipmentReportRequest;
use App\Models\Equipment;
use App\Services\EquipmentReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class EquipmentReportController extends Controller
{
    /**
     * Reporte de un equipo para un rango de fechas.
     */
    public function __invoke(
        EquipmentReportRequest $request,
        Equipment $equipment,
        EquipmentReportService $reports,
    ): JsonResponse {
        $report = $reports->generate(
            $equipment,
            CarbonImmutable::parse($request->validated('from')),
            CarbonImmutable::parse($request->validated('to')),
        );

        return response()->json(['data' => $report]);
    }
}