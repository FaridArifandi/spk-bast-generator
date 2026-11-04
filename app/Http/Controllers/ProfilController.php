<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function show(Request $request)
    {
        return view('profil.show', ['user' => $request->user()->load('pegawai')]);
    }

    public function ubahPassword(Request $request)
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('sukses', 'Password berhasil diubah.');
    }
}
