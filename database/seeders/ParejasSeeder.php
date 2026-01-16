<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\Pareja;
use Illuminate\Database\Seeder;

class ParejasSeeder extends Seeder
{
    /**
     * Crear 12 equipos y 50 parejas con sus usuarios distribuidos en esos equipos.
     */
    public function run(): void
    {
        $this->command->info('Creando 12 equipos...');

        $equipos = Equipo::factory(12)->create();

        $this->command->info('Creando 50 parejas con sus usuarios...');

        $equipoIndex = 0;
        for ($i = 0; $i < 50; $i++) {
            Pareja::factory()
                ->conUsuarios()
                ->create([
                    'equipo_id' => $equipos[$equipoIndex]->id,
                ]);

            // Rotar entre los 12 equipos
            $equipoIndex = ($equipoIndex + 1) % 12;
        }

        $this->command->info('¡12 equipos y 50 parejas creadas exitosamente!');
    }
}
