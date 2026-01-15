<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmPasswordController extends Controller
{
    /**
     * Show the password confirmation view.
     */
    public function show(): Response
    {
        return Inertia::render('auth/confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors([
                'password' => ['La contraseña proporcionada es incorrecta.'],
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended();
    }
}
