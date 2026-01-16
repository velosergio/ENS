<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Equipo extends Model
{
    /** @use HasFactory<\Database\Factories\EquipoFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'numero',
        'responsable_id',
        'consiliario_nombre',
    ];

    /**
     * Obtener las parejas del equipo.
     */
    public function parejas(): HasMany
    {
        return $this->hasMany(Pareja::class);
    }

    /**
     * Obtener los usuarios del equipo a través de las parejas.
     */
    public function usuarios(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Pareja::class);
    }

    /**
     * Obtener el responsable del equipo.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtener la pareja responsable del equipo.
     */
    public function parejaResponsable(): ?Pareja
    {
        if (! $this->responsable_id) {
            return null;
        }

        return $this->responsable->pareja;
    }

    /**
     * Verificar si el equipo tiene parejas asignadas.
     */
    public function tieneParejas(): bool
    {
        return $this->parejas()->exists();
    }

    /**
     * Scope para buscar equipos por término (número, consiliario, responsable).
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('numero', 'like', "%{$termino}%")
                ->orWhere('consiliario_nombre', 'like', "%{$termino}%")
                ->orWhereHas('responsable', function ($query) use ($termino) {
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
