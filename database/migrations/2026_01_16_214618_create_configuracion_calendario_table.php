<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracion_calendario', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_evento', ['general', 'formacion', 'retiro_espiritual', 'reunion_equipo', 'cumpleanos'])->unique();
            $table->string('color', 7)->default('#3b82f6'); // hex color
            $table->string('icono')->nullable(); // nombre del icono de lucide
            $table->timestamps();
        });

        // Insertar valores por defecto
        DB::table('configuracion_calendario')->insert([
            ['tipo_evento' => 'general', 'color' => '#3b82f6', 'icono' => 'Calendar', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_evento' => 'formacion', 'color' => '#10b981', 'icono' => 'BookOpen', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_evento' => 'retiro_espiritual', 'color' => '#8b5cf6', 'icono' => 'Church', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_evento' => 'reunion_equipo', 'color' => '#f59e0b', 'icono' => 'Users', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_evento' => 'cumpleanos', 'color' => '#ec4899', 'icono' => 'Cake', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_calendario');
    }
};
