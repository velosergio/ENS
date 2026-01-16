<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckParejaActiva
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario no está autenticado, continuar
        if (! $user) {
            return $next($request);
        }

        // Cargar la pareja si existe
        $pareja = $user->pareja;

        // Si el usuario tiene pareja y está retirada, cerrar sesión
        if ($pareja && ! $pareja->estaActiva()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirigir al login con mensaje
            return redirect()->route('login')
                ->with('status', 'Su pareja ha sido retirada del movimiento. Ya no tiene acceso a la plataforma.');
        }

        return $next($request);
    }
}
