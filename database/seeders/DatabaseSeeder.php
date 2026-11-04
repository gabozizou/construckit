<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // Usuario de prueba para token en POST /api/login.
        User::factory()->create([
            'name' => 'Supervisor Demo',
            'email' => 'demo@construckit.cl',
            'password' => 'password',
        ]);

        $this->call(EquipmentSeeder::class);
    }
}
