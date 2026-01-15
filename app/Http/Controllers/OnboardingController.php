<?php

namespace App\Http\Controllers;

use App\Http\Requests\Onboarding\StepFourRequest;
use App\Http\Requests\Onboarding\StepOneRequest;
use App\Http\Requests\Onboarding\StepThreeRequest;
use App\Http\Requests\Onboarding\StepTwoRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Mostrar paso uno del onboarding.
     */
    public function pasoUno(): Response|RedirectResponse
    {
        return Inertia::render('onboarding/paso-uno', [
            'user' => Auth::user() ?? null,
        ]);
    }

    /**
     * Guardar datos de la etapa 1, crear usuario y autenticarlo.
     */
    public function guardarPasoUno(StepOneRequest $request)
    {
        // Si el usuario ya está autenticado, actualizar sus datos
        if (Auth::check()) {
            $user = Auth::user();
            $user->update([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'celular' => $request->celular,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
        } else {
            // Crear nuevo usuario
            $user = User::create([
                'name' => $request->nombres.' '.$request->apellidos,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'celular' => $request->celular,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // Autenticar al usuario recién creado
            Auth::login($user);
        }

        return redirect('/registro/paso-dos');
    }

    /**
     * Mostrar paso dos del onboarding.
     */
    public function pasoDos(): Response|RedirectResponse
    {
        $user = Auth::user();

        // Si no completó el paso 1, redirigir (solo esta validación es necesaria)
        if (! $user || ! $user->nombres || ! $user->apellidos || ! $user->celular || ! $user->fecha_nacimiento) {
            return redirect('/registro/paso-uno');
        }

        return Inertia::render('onboarding/paso-dos', [
            'user' => $user,
        ]);
    }

    /**
     * Guardar datos de la etapa 2 y redirigir al paso tres.
     */
    public function guardarPasoDos(StepTwoRequest $request)
    {
        $user = Auth::user();

        $updateData = [];
        
        if ($request->has('sexo') && $request->sexo) {
            $updateData['sexo'] = $request->sexo;
        }
        
        if ($request->has('foto_base64')) {
            $updateData['foto_base64'] = $request->foto_base64;
        }

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        // Si viene de un guardado automático, no redirigir
        if ($request->has('_preserve')) {
            return back();
        }

        // Validar que el sexo esté presente antes de continuar
        if (! $user->sexo) {
            return back()->withErrors(['sexo' => 'Debes seleccionar tu sexo para continuar.']);
        }

        return redirect('/registro/paso-tres');
    }

    /**
     * Mostrar paso tres del onboarding.
     */
    public function pasoTres(): Response|RedirectResponse
    {
        $user = Auth::user();

        // Si no completó el paso 1, redirigir
        if (! $user || ! $user->nombres || ! $user->apellidos || ! $user->celular || ! $user->fecha_nacimiento) {
            return redirect('/registro/paso-uno');
        }

        // Si no completó el paso 2, redirigir
        if (! $user->sexo) {
            return redirect('/registro/paso-dos');
        }

        return Inertia::render('onboarding/paso-tres', [
            'user' => $user,
        ]);
    }

    /**
     * Guardar datos de la etapa 3 y redirigir al paso cuatro.
     */
    public function guardarPasoTres(StepThreeRequest $request)
    {
        $user = Auth::user();

        if ($request->skip_partner) {
            $user->update([
                'pareja_id' => null,
            ]);
        } elseif ($request->pareja_id) {
            $user->update([
                'pareja_id' => $request->pareja_id,
            ]);
        }

        // Si viene de un guardado automático, no redirigir
        if ($request->has('_preserve')) {
            return back();
        }

        return redirect('/registro/paso-cuatro');
    }

    /**
     * Mostrar paso cuatro del onboarding.
     */
    public function pasoCuatro(): Response|RedirectResponse
    {
        $user = Auth::user();

        // Si no completó el paso 1, redirigir
        if (! $user || ! $user->nombres || ! $user->apellidos || ! $user->celular || ! $user->fecha_nacimiento) {
            return redirect('/registro/paso-uno');
        }

        // Si no completó el paso 2, redirigir
        if (! $user->sexo) {
            return redirect('/registro/paso-dos');
        }

        return Inertia::render('onboarding/paso-cuatro', [
            'user' => $user,
        ]);
    }

    /**
     * Guardar datos de la etapa 4 y completar onboarding.
     */
    public function guardarPasoCuatro(StepFourRequest $request)
    {
        $user = Auth::user();

        $user->update([
            'equipo_id' => $request->equipo_id,
        ]);

        return redirect('/dashboard');
    }

    /**
     * Buscar usuarios para la etapa 3 (búsqueda en tiempo real).
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $user = Auth::user();
        $search = $request->get('search', '');

        if (! $user->sexo) {
            return response()->json([]);
        }

        $sexoOponente = $user->sexo === 'masculino' ? 'femenino' : 'masculino';

        $users = User::query()
            ->where('id', '!=', $user->id)
            ->where('sexo', $sexoOponente)
            ->where(function ($query) use ($search) {
                $query->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('celular', 'like', "%{$search}%");
            })
            ->select('id', 'nombres', 'apellidos', 'email', 'foto_base64')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    /**
     * Determinar el paso actual del usuario basado en sus datos.
     */
    private function getCurrentStep(User $user): int
    {
        if (! $user->nombres || ! $user->apellidos || ! $user->celular || ! $user->fecha_nacimiento) {
            return 1;
        }

        if (! $user->sexo) {
            return 2;
        }

        // Verificar si el usuario ha completado la etapa 3
        // Si pareja_id es null pero el usuario ya pasó por la etapa 3, significa que eligió "skip"
        // Por ahora, si no tiene pareja_id, necesita completar la etapa 3
        if ($user->pareja_id === null) {
            // Verificar si el usuario tiene otros datos de la etapa 3 completados
            // Por ahora, simplemente verificamos si tiene sexo (etapa 2 completada)
            return 3;
        }

        return 4;
    }
}
