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
                'foto_base64' => $user->foto_base64,
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
        $fotoBase64Original = $user->foto_base64;
        
        $user->fill($request->validated());

        // Generar thumbnails si se actualiza la foto
        $fotoBase64 = $request->foto_base64 ?? $fotoBase64Original;
        if ($fotoBase64 !== $fotoBase64Original && $fotoBase64 !== null && $fotoBase64 !== '') {
            $thumbnails = $this->imageService->generateThumbnails($fotoBase64);
            $user->foto_base64 = $fotoBase64;
            $user->foto_thumbnail_50 = $thumbnails['50'];
            $user->foto_thumbnail_100 = $thumbnails['100'];
            $user->foto_thumbnail_500 = $thumbnails['500'];
        }

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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
