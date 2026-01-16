<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'numero_equipo',
        'foto_base64',
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
}
