<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sexo = fake()->randomElement(['masculino', 'femenino']);
        $nombres = $sexo === 'masculino' ? fake()->firstNameMale() : fake()->firstNameFemale();

        return [
            'nombres' => $nombres,
            'apellidos' => fake()->lastName(),
            'celular' => fake()->phoneNumber(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'sexo' => $sexo,
            'foto_path' => null,
            'foto_thumbnail_50' => null,
            'foto_thumbnail_100' => null,
            'foto_thumbnail_500' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'pareja_id' => null,
            'rol' => 'equipista',
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicar que el usuario es masculino.
     */
    public function masculino(): static
    {
        return $this->state(fn (array $attributes) => [
            'sexo' => 'masculino',
            'nombres' => fake()->firstNameMale(),
        ]);
    }

    /**
     * Indicar que el usuario es femenino.
     */
    public function femenino(): static
    {
        return $this->state(fn (array $attributes) => [
            'sexo' => 'femenino',
            'nombres' => fake()->firstNameFemale(),
        ]);
    }

    /**
     * Indicar que el usuario tiene rol mango.
     */
    public function mango(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'mango',
        ]);
    }

    /**
     * Indicar que el usuario tiene rol admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'admin',
        ]);
    }

    /**
     * Indicar que el usuario tiene rol equipista.
     */
    public function equipista(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'equipista',
        ]);
    }

    /**
     * Indicar que el email del usuario no está verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicar que el usuario tiene autenticación de dos factores configurada.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
