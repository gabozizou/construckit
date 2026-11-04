<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Punto 3: reporte por equipo y rango de fechas.
 */
class EquipmentReportTest extends TestCase
{
    use RefreshDatabase;

    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());

        $this->equipment = Equipment::create([
            'code' => 'CAM-001',
            'type' => 'camion',
            'brand' => 'Volvo',
            'model' => 'FH16',
            'current_hourmeter' => 1150,
        ]);

        // Escenario del ejemplo del README: 3 cargas en octubre,
        // más una antes y otra después del rango que NO deben contarse.
        $this->equipment->fuelLoads()->createMany([
            ['loaded_at' => '2026-09-25 08:00:00', 'liters' => 90,  'amount_clp' => 99000,  'hourmeter' => 950],  // fuera
            ['loaded_at' => '2026-10-01 08:00:00', 'liters' => 100, 'amount_clp' => 110000, 'hourmeter' => 1000],
            ['loaded_at' => '2026-10-15 08:00:00', 'liters' => 120, 'amount_clp' => 132000, 'hourmeter' => 1050],
            ['loaded_at' => '2026-10-31 23:30:00', 'liters' => 110, 'amount_clp' => 121000, 'hourmeter' => 1100], // último día, de noche
            ['loaded_at' => '2026-11-02 08:00:00', 'liters' => 115, 'amount_clp' => 126500, 'hourmeter' => 1150], // fuera
        ]);

        $this->equipment->maintenances()->createMany([
            ['type' => 'preventiva', 'performed_on' => '2026-10-10', 'hourmeter' => 1020, 'cost_clp' => 300000, 'description' => 'Preventiva'],
            ['type' => 'correctiva', 'performed_on' => '2026-10-31', 'hourmeter' => 1100, 'cost_clp' => 50000,  'description' => 'Correctiva'],
            ['type' => 'correctiva', 'performed_on' => '2026-11-01', 'hourmeter' => 1110, 'cost_clp' => 999999, 'description' => 'Fuera de rango'],
        ]);
    }

    public function test_report_calculates_totals_and_liters_per_hour(): void
    {
        $response = $this->getJson($this->reportUrl('2026-10-01', '2026-10-31'))->assertOk();

        // Totales: solo las 3 cargas y 2 mantenciones de octubre.
        $response
            ->assertJsonPath('data.fuel.loads_count', 3)
            ->assertJsonPath('data.fuel.total_cost_clp', 363000)
            ->assertJsonPath('data.maintenance.count', 2)
            ->assertJsonPath('data.maintenance.total_cost_clp', 350000);

        $this->assertEquals(330, $response->json('data.fuel.total_liters'));

        // Rendimiento: (120 + 110) L / (1100 - 1000) h = 2,3 L/h.
        // Los 100 L de la primera carga no cuentan.
        $this->assertEquals(100, $response->json('data.performance.hours_used'));
        $this->assertEquals(2.3, $response->json('data.performance.liters_per_hour'));
        $this->assertNull($response->json('data.performance.note'));
    }

    public function test_performance_is_null_with_a_single_load(): void
    {
        $response = $this->getJson($this->reportUrl('2026-10-15', '2026-10-15'))->assertOk();

        $response->assertJsonPath('data.fuel.loads_count', 1);
        $this->assertNull($response->json('data.performance.liters_per_hour'));
        $this->assertNotNull($response->json('data.performance.note'));
    }

    public function test_report_without_data_returns_zeros(): void
    {
        $response = $this->getJson($this->reportUrl('2026-01-01', '2026-01-31'))->assertOk();

        $response
            ->assertJsonPath('data.fuel.loads_count', 0)
            ->assertJsonPath('data.fuel.total_cost_clp', 0)
            ->assertJsonPath('data.maintenance.total_cost_clp', 0);

        $this->assertEquals(0, $response->json('data.fuel.total_liters'));
        $this->assertNull($response->json('data.performance.liters_per_hour'));
    }

    public function test_report_rejects_an_inverted_date_range(): void
    {
        $this->getJson($this->reportUrl('2026-10-31', '2026-10-01'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_report_requires_dates(): void
    {
        $this->getJson("/api/equipment/{$this->equipment->id}/report")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['from', 'to']);
    }

    private function reportUrl(string $from, string $to): string
    {
        return "/api/equipment/{$this->equipment->id}/report?from={$from}&to={$to}";
    }
}