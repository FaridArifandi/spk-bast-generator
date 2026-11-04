<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\DokumenDetail;
use App\Models\Kegiatan;
use App\Models\Pegawai;
use App\Models\Penugasan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        return view('master.kegiatan.index', [
            'items' => Kegiatan::with('ketuaTim')->cari($request->q)->orderBy('kode')->paginate(25)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('master.kegiatan.form', ['item' => new Kegiatan(['satuan' => 'Dokumen']), 'pegawai' => Pegawai::orderBy('nama')->get()]);
    }

    public function store(Request $request)
    {
        Kegiatan::create($this->validasi($request));
        return redirect()->route('kegiatan.index')->with('sukses', 'Kegiatan ditambahkan.');
    }

    public function edit(Kegiatan $kegiatan)
    {
        return view('master.kegiatan.form', ['item' => $kegiatan, 'pegawai' => Pegawai::orderBy('nama')->get()]);
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $kegiatan->update($this->validasi($request, $kegiatan));
        return redirect()->route('kegiatan.index')->with('sukses', 'Kegiatan diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan)
    {
        if (Penugasan::where('kegiatan_id', $kegiatan->id)->exists() || DokumenDetail::where('kegiatan_id', $kegiatan->id)->exists()) {
            return back()->with('galat', 'Kegiatan sudah dipakai pada penugasan/dokumen, tidak bisa dihapus.');
        }
        $kegiatan->delete();
        return redirect()->route('kegiatan.index')->with('sukses', 'Kegiatan dihapus.');
    }

    private function validasi(Request $request, ?Kegiatan $kegiatan = null): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:30', Rule::unique('kegiatan', 'kode')->ignore($kegiatan)],
            'uraian_tugas' => ['required', 'string', 'max:500'],
            'satuan' => ['required', 'string', 'max:60'],
            'harga_satuan' => ['nullable', 'integer', 'min:0'],
            'beban_anggaran' => ['nullable', 'string', 'max:255'],
            'buku_pedoman' => ['nullable', 'string', 'max:255'],
            'ketua_tim_id' => ['nullable', 'exists:pegawai,id'],
        ]);
    }
}
