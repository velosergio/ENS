<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionCalendario extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'configuracion_calendario';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tipo_evento',
        'color',
        'icono',
    ];

    /**
     * Obtener configuración por tipo de evento.
     */
    public static function porTipo(string $tipo): ?self
    {
        return self::where('tipo_evento', $tipo)->first();
    }

    /**
     * Obtener todas las configuraciones como array asociativo.
     *
     * @return array<string, array{color: string, icono: string|null}>
     */
    public static function todas(): array
    {
        return self::all()->keyBy('tipo_evento')->map(function ($config) {
            return [
                'color' => $config->color,
                'icono' => $config->icono,
            ];
        })->toArray();
    }
}
