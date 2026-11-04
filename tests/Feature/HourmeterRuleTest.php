<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Punto 2: registro de cargas y mantenciones con la regla del horómetro.
 */
class HourmeterRuleTest extends TestCase
{
    // Recrea las tablas antes de cada test: cada test parte con la base vacía.
    use RefreshDatabase;

    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        // Simula un usuario autenticado con Sanctum (sin pasar por /login).
        Sanctum::actingAs(User::factory()->create());

        // Equipo base con horómetro en 1000 para todos los tests.
        $this->equipment = Equipment::create([
            'code' => 'CAM-001',
            'type' => 'camion',
            'brand' => 'Volvo',
            'model' => 'FH16',
            'current_hourmeter' => 1000,
        ]);
    }

    public function test_fuel_load_with_higher_hourmeter_updates_equipment(): void
    {
        $this->postJson($this->fuelLoadsUrl(), $this->fuelLoad(1050))
            ->assertCreated();

        $this->assertDatabaseCount('fuel_loads', 1);
        $this->assertSame(1050.0, $this->currentHourmeter());
    }

    public function test_fuel_load_with_equal_hourmeter_is_accepted_without_changing_equipment(): void
    {
        $this->postJson($this->fuelLoadsUrl(), $this->fuelLoad(1000))
            ->assertCreated();

        $this->assertDatabaseCount('fuel_loads', 1);
        $this->assertSame(1000.0, $this->currentHourmeter());
    }

    public function test_fuel_load_with_lower_hourmeter_is_rejected(): void
    {
        $this->postJson($this->fuelLoadsUrl(), $this->fuelLoad(990))
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.hourmeter.0',
                'El horómetro (990) no puede ser menor al último registrado (1000).',
            );

        // No se guarda nada y el equipo queda igual.
        $this->assertDatabaseCount('fuel_loads', 0);
        $this->assertSame(1000.0, $this->currentHourmeter());
    }

    public function test_maintenance_with_higher_hourmeter_updates_equipment(): void
    {
        $this->postJson($this->maintenancesUrl(), $this->maintenance(1300))
            ->assertCreated();

        $this->assertDatabaseCount('maintenances', 1);
        $this->assertSame(1300.0, $this->currentHourmeter());
    }

    public function test_maintenance_with_lower_hourmeter_is_rejected(): void
    {
        $this->postJson($this->maintenancesUrl(), $this->maintenance(900))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hourmeter']);

        $this->assertDatabaseCount('maintenances', 0);
        $this->assertSame(1000.0, $this->currentHourmeter());
    }

    public function test_rule_applies_across_fuel_loads_and_maintenances(): void
    {
        // Una mantención sube el horómetro a 1200...
        $this->postJson($this->maintenancesUrl(), $this->maintenance(1200))->assertCreated();

        // ...y después una carga con 1100 (menor) se rechaza.
        $this->postJson($this->fuelLoadsUrl(), $this->fuelLoad(1100))->assertUnprocessable();

        $this->assertSame(1200.0, $this->currentHourmeter());
    }

    public function test_invalid_fuel_load_data_is_rejected(): void
    {
        $this->postJson($this->fuelLoadsUrl(), [
            'liters' => 0,
            'amount_clp' => -5,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['loaded_at', 'liters', 'amount_clp', 'hourmeter']);
    }

    private function fuelLoadsUrl(): string
    {
        return "/api/equipment/{$this->equipment->id}/fuel-loads";
    }

    private function maintenancesUrl(): string
    {
        return "/api/equipment/{$this->equipment->id}/maintenances";
    }

    private function fuelLoad(float $hourmeter): array
    {
        return [
            'loaded_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'liters' => 100,
            'amount_clp' => 110000,
            'hourmeter' => $hourmeter,
        ];
    }

    private function maintenance(float $hourmeter): array
    {
        return [
            'type' => 'preventiva',
            'performed_on' => now()->toDateString(),
            'hourmeter' => $hourmeter,
            'cost_clp' => 300000,
            'description' => 'Mantención de prueba',
        ];
    }

    private function currentHourmeter(): float
    {
        return (float) $this->equipment->fresh()->current_hourmeter;
    }
}