<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pareja extends Model
{
    /** @use HasFactory<\Database\Factories\ParejaFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fecha_ingreso',
        'equipo_id',
        'foto_base64',
        'foto_thumbnail_50',
        'foto_thumbnail_100',
        'foto_thumbnail_500',
        'estado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
        ];
    }

    /**
     * Scope para obtener solo parejas activas.
     */
    public function scopeActiva($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Verificar si la pareja está activa.
     */
    public function estaActiva(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Retirar la pareja del movimiento.
     */
    public function retirar(): void
    {
        $this->estado = 'retirado';
        $this->save();
    }

    /**
     * Reactivar la pareja en el movimiento.
     */
    public function reactivar(): void
    {
        $this->estado = 'activo';
        $this->save();
    }

    /**
     * Obtener el equipo al que pertenece la pareja.
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    /**
     * Obtener los usuarios de la pareja.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Obtener el usuario masculino.
     */
    public function el(): ?User
    {
        return $this->usuarios()->where('sexo', 'masculino')->first();
    }

    /**
     * Obtener el usuario femenino.
     */
    public function ella(): ?User
    {
        return $this->usuarios()->where('sexo', 'femenino')->first();
    }

    /**
     * Scope para filtrar parejas que no tienen usuarios con rol mango.
     */
    public function scopeSinMango($query)
    {
        return $query->whereDoesntHave('usuarios', function ($q) {
            $q->where('rol', 'mango');
        });
    }

    /**
     * Scope para buscar parejas por término (nombres, emails, número de equipo).
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->whereHas('equipo', function ($query) use ($termino) {
                $query->where('numero', 'like', "%{$termino}%");
            })
                ->orWhereHas('usuarios', function ($query) use ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('nombres', 'like', "%{$termino}%")
                            ->orWhere('apellidos', 'like', "%{$termino}%")
                            ->orWhere('email', 'like', "%{$termino}%")
                            ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$termino}%"]);
                    });
                });
        });
    }
}
