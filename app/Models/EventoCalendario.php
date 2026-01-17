<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoCalendario extends Model
{
    /** @use HasFactory<\Database\Factories\EventoCalendarioFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eventos_calendario';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'es_todo_el_dia',
        'tipo',
        'alcance',
        'equipo_id',
        'creado_por',
        'color',
        'icono',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'es_todo_el_dia' => 'boolean',
        ];
    }

    /**
     * Obtener el usuario que creó el evento.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Obtener el equipo al que pertenece el evento (si aplica).
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    /**
     * Scope para filtrar eventos por equipo.
     */
    public function scopePorEquipo($query, int $equipoId)
    {
        return $query->where('equipo_id', $equipoId);
    }

    /**
     * Scope para filtrar eventos globales.
     */
    public function scopeGlobales($query)
    {
        return $query->where('alcance', 'global');
    }

    /**
     * Scope para filtrar eventos por rango de fechas.
     */
    public function scopePorRangoFechas($query, string $fechaInicio, string $fechaFin)
    {
        return $query->where(function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                ->orWhere(function ($query) use ($fechaInicio, $fechaFin) {
                    $query->where('fecha_inicio', '<=', $fechaInicio)
                        ->where('fecha_fin', '>=', $fechaFin);
                });
        });
    }

    /**
     * Scope para filtrar eventos por tipo.
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Verificar si el evento es global.
     */
    public function esGlobal(): bool
    {
        return $this->alcance === 'global';
    }

    /**
     * Verificar si el evento pertenece a un equipo específico.
     */
    public function perteneceAEquipo(int $equipoId): bool
    {
        return $this->equipo_id === $equipoId;
    }

    /**
     * Verificar si el evento está en un rango de fechas.
     */
    public function estaEnRango(string $fechaInicio, string $fechaFin): bool
    {
        return ($this->fecha_inicio->format('Y-m-d') <= $fechaFin) &&
               ($this->fecha_fin->format('Y-m-d') >= $fechaInicio);
    }

    /**
     * Formatear el evento a formato FullCalendar.
     *
     * @param  array<string, array{color: string, icono: string|null}>  $configuraciones  Configuraciones de colores e iconos por tipo
     * @return array<string, mixed>
     */
    public function toFullCalendarFormat(array $configuraciones = []): array
    {
        // Obtener color e icono de la configuración si no están definidos en el evento
        $configTipo = $configuraciones[$this->tipo] ?? null;
        $color = $this->color ?? $configTipo['color'] ?? '#3b82f6';
        $icono = $this->icono ?? $configTipo['icono'] ?? null;

        // Construir fecha/hora para FullCalendar
        $startDateTime = $this->fecha_inicio->format('Y-m-d');
        $endDateTime = $this->fecha_fin->format('Y-m-d');

        // Si no es todo el día y tiene hora, agregar hora
        // hora_inicio y hora_fin son campos 'time' (strings 'H:i:s')
        if (! $this->es_todo_el_dia && $this->hora_inicio && $this->hora_fin) {
            // Crear objeto Carbon con fecha y hora local
            $startCarbon = $this->fecha_inicio->copy()->setTimeFromTimeString($this->hora_inicio);
            $endCarbon = $this->fecha_fin->copy()->setTimeFromTimeString($this->hora_fin);

            // Convertir a UTC para que FullCalendar lo interprete correctamente
            // FullCalendar espera fechas en UTC cuando tienen hora
            $startDateTime = $startCarbon->utc()->format('Y-m-d\TH:i:s\Z');
            $endDateTime = $endCarbon->utc()->format('Y-m-d\TH:i:s\Z');
        } else {
            // Para eventos de todo el día, FullCalendar requiere fecha end exclusiva (siguiente día)
            $endDateTime = $this->fecha_fin->copy()->addDay()->format('Y-m-d');
        }

        return [
            'id' => $this->id,
            'title' => $this->titulo,
            'start' => $startDateTime,
            'end' => $endDateTime,
            'allDay' => $this->es_todo_el_dia,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'descripcion' => $this->descripcion,
                'tipo' => $this->tipo,
                'alcance' => $this->alcance,
                'equipo_id' => $this->equipo_id,
                'icono' => $icono,
                'creado_por' => $this->creadoPor ? [
                    'id' => $this->creadoPor->id,
                    'nombres' => $this->creadoPor->nombres,
                    'apellidos' => $this->creadoPor->apellidos,
                    'email' => $this->creadoPor->email,
                ] : null,
                'equipo' => $this->equipo ? [
                    'id' => $this->equipo->id,
                    'numero' => $this->equipo->numero,
                ] : null,
            ],
        ];
    }
}
