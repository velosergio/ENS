<?php

namespace Database\Seeders;

use App\Models\Pareja;
use Illuminate\Database\Seeder;

class ParejasSeeder extends Seeder
{
    /**
     * Crear 50 parejas con sus usuarios para probar el scroll infinito.
     */
    public function run(): void
    {
        $this->command->info('Creando 50 parejas con sus usuarios...');

        Pareja::factory(50)
            ->conUsuarios()
            ->create();

        $this->command->info('¡50 parejas creadas exitosamente!');
    }
}
