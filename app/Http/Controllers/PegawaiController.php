<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        return view('master.pegawai.index', [
            'items' => Pegawai::cari($request->q)->orderBy('nama')->paginate(25)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('master.pegawai.form', ['item' => new Pegawai()]);
    }

    public function store(Request $request)
    {
        Pegawai::create($this->validasi($request));
        return redirect()->route('pegawai.index')->with('sukses', 'Pegawai ditambahkan.');
    }

    public function edit(Pegawai $pegawai)
    {
        return view('master.pegawai.form', ['item' => $pegawai]);
    }

    public function update(Request $request, Pegawai $pegawai)
    {
        $pegawai->update($this->validasi($request));
        return redirect()->route('pegawai.index')->with('sukses', 'Data pegawai diperbarui.');
    }

    public function destroy(Pegawai $pegawai)
    {
        $alasan = null;
        if (\App\Models\Dokumen::withTrashed()->where(fn ($q) => $q->where('pegawai_id', $pegawai->id)->orWhere('ppk_id', $pegawai->id)->orWhere('ketua_tim_id', $pegawai->id)->orWhere('pml_pegawai_id', $pegawai->id))->exists()) {
            $alasan = 'dokumen SPK/BAST';
        } elseif (\App\Models\BastSmPpk::withTrashed()->where(fn ($q) => $q->where('ppk_id', $pegawai->id)->orWhere('ketua_tim_id', $pegawai->id))->exists()) {
            $alasan = 'BAST SM → PPK';
        } elseif (\App\Models\Penugasan::where(fn ($q) => $q->where('pegawai_id', $pegawai->id)->orWhere('pml_pegawai_id', $pegawai->id))->exists()) {
            $alasan = 'penugasan kegiatan';
        } elseif (\App\Models\Kegiatan::where('ketua_tim_id', $pegawai->id)->exists()) {
            $alasan = 'master kegiatan (sebagai ketua tim)';
        } elseif (\App\Models\User::where('pegawai_id', $pegawai->id)->exists()) {
            $alasan = 'akun pengguna sistem';
        }

        if ($alasan) {
            return back()->with('galat', "Pegawai masih dirujuk pada {$alasan}, tidak bisa dihapus.");
        }

        $pegawai->delete();
        return redirect()->route('pegawai.index')->with('sukses', 'Pegawai dihapus.');
    }

    private function validasi(Request $request): array
    {
        $d = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'pangkat' => ['nullable', 'string', 'max:100'],
            'golongan' => ['nullable', 'string', 'max:50'],
        ]);
        $d['is_ppk'] = $request->boolean('is_ppk');
        return $d;
    }
}
