<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\ImageService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected ImageService $imageService,
    ) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'celular' => $user->celular,
                'fecha_nacimiento' => $user->fecha_nacimiento?->format('Y-m-d'),
                'sexo' => $user->sexo,
                'foto_url' => $user->foto_url,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        // Guardar imagen si se actualiza
        $userImages = $this->imageService->saveImageFromFile(
            $request->file('foto'),
            'users',
            $user->foto_path,
        );

        $user->foto_path = $userImages['original'];
        $user->foto_thumbnail_50 = $userImages['50'];
        $user->foto_thumbnail_100 = $userImages['100'];
        $user->foto_thumbnail_500 = $userImages['500'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Eliminar imagen del usuario
        if ($user->foto_path) {
            $this->imageService->deleteImage($user->foto_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
