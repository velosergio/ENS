<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticatePareja
{
    /**
     * Autenticar usuario permitiendo login con cualquiera de los emails de la pareja.
     */
    public function __invoke(Request $request): ?User
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (! $email || ! $password) {
            return null;
        }

        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        if (! $user) {
            return null;
        }

        // Verificar contraseña
        if (! Hash::check($password, $user->password)) {
            return null;
        }

        // Verificar si la pareja está activa
        if ($user->pareja && ! $user->pareja->estaActiva()) {
            return null;
        }

        // Verificar si el usuario pertenece a una pareja
        // Si pertenece, cualquier usuario de la pareja puede iniciar sesión
        // pero siempre autenticamos con el usuario que tiene el email proporcionado
        return $user;
    }
}
