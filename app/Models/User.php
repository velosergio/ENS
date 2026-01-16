<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'nombres',
        'apellidos',
        'celular',
        'fecha_nacimiento',
        'sexo',
        'foto_base64',
        'foto_thumbnail_50',
        'foto_thumbnail_100',
        'foto_thumbnail_500',
        'pareja_id',
        'equipo_id',
        'rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'fecha_nacimiento' => 'date',
        ];
    }

    /**
     * Obtener la pareja a la que pertenece el usuario.
     */
    public function pareja(): BelongsTo
    {
        return $this->belongsTo(Pareja::class);
    }

    /**
     * Verificar si el usuario es mango.
     */
    public function esMango(): bool
    {
        return $this->rol === 'mango';
    }

    /**
     * Verificar si el usuario es admin.
     */
    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /**
     * Verificar si el usuario es equipista.
     */
    public function esEquipista(): bool
    {
        return $this->rol === 'equipista';
    }

    /**
     * Verificar si el usuario tiene un rol específico.
     */
    public function tieneRol(string $rol): bool
    {
        return $this->rol === $rol;
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados.
     */
    public function tieneAlgunRol(array $roles): bool
    {
        return in_array($this->rol, $roles);
    }
}
