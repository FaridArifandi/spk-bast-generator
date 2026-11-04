<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $cred = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $cred['username'] = strtolower(trim($cred['username']));

        if (! Auth::attempt(['username' => $cred['username'], 'password' => $cred['password'], 'aktif' => true], $request->boolean('ingat'))) {
            throw ValidationException::withMessages(['username' => 'Username atau password salah.']);
        }

        $request->session()->regenerate();

        return match (Auth::user()->roleEnum()) {
            Role::Ppk => redirect()->intended(route('verifikasi.index')),
            Role::Katim => redirect()->intended(route('penugasan.index')),
            default => redirect()->intended(route('dashboard')),
        };
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
