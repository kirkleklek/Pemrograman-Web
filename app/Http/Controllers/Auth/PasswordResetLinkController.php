<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Menampilkan formulir permintaan reset password.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Mengirim tautan reset password ke email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if (
            $status === Password::RESET_LINK_SENT
            || $status === Password::INVALID_USER
        ) {
            // Pesan yang sama untuk menghindari pengungkapan
            // apakah suatu email terdaftar.
            return back()->with(
                'status',
                'Jika email terdaftar, instruksi reset password akan dikirim.'
            );
        }

        return back()->withErrors([
            'email' => 'Permintaan terlalu sering. Silakan coba lagi nanti.',
        ]);
    }
}
