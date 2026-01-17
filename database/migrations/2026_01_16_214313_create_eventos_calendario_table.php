<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eventos_calendario', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->boolean('es_todo_el_dia')->default(false);
            $table->enum('tipo', ['general', 'formacion', 'retiro_espiritual', 'reunion_equipo'])->default('general');
            $table->enum('alcance', ['equipo', 'global'])->default('equipo');
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->string('color', 7)->default('#3b82f6'); // hex color
            $table->string('icono')->nullable(); // nombre del icono de lucide
            $table->timestamps();

            // Índices
            $table->index('fecha_inicio');
            $table->index('equipo_id');
            $table->index('tipo');
            $table->index('alcance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_calendario');
    }
};
