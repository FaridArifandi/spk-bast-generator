<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.pengguna.index', [
            'items' => User::with('pegawai')->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('nama', 'like', "%$v%")->orWhere('username', 'like', "%$v%")))
                ->orderBy('nama')->paginate(25)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.pengguna.form', ['item' => new User(['aktif' => true, 'role' => 'PENGGUNA']), 'pegawai' => Pegawai::orderBy('nama')->get(), 'roles' => Role::options()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['password'] = $request->validate(['password' => ['required', Password::min(8)]])['password'];
        User::create($data);

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.pengguna.form', ['item' => $user, 'pegawai' => Pegawai::orderBy('nama')->get(), 'roles' => Role::options()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validasi($request, $user);

        if ($request->filled('password')) {
            $data['password'] = $request->validate(['password' => [Password::min(8)]])['password'];
        }

        // jangan sampai sistem tanpa admin aktif
        if ($user->isAdmin() && ($data['role'] !== 'ADMIN' || ! $data['aktif']) && User::where('role', 'ADMIN')->where('aktif', true)->count() <= 1) {
            return back()->withInput()->with('galat', 'Harus ada minimal satu Administrator aktif.');
        }

        $user->update($data);

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Pengguna diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('galat', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        }
        if ($user->isAdmin() && User::where('role', 'ADMIN')->where('aktif', true)->count() <= 1) {
            return back()->with('galat', 'Harus ada minimal satu Administrator aktif.');
        }
        $user->delete();

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Pengguna dihapus.');
    }

    private function validasi(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(Role::options()))],
            'sub_role' => ['nullable', 'string', 'max:40'],
            'pegawai_id' => ['nullable', 'exists:pegawai,id'],
        ]);
        $data['username'] = strtolower($data['username']);
        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
