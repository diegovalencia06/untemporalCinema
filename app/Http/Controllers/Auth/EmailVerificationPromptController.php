<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        // Si el usuario ya está verificado, lo manda al inicio
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/');
        }

        // Si NO está verificado, le muestra tu pantalla de Vue en lugar del error
        return Inertia::render('Auth/MustVerifyEmail', [
            'status' => session('status'),
        ]);
    }
}