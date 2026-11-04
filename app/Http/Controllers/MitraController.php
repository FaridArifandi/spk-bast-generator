<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MitraController extends Controller
{
    public function index(Request $request)
    {
        $items = Mitra::cari($request->q)
            ->when($request->kategori, fn ($q, $k) => $q->where('kategori', $k))
            ->orderBy('nama')->paginate(25)->withQueryString();

        return view('master.mitra.index', [
            'items' => $items,
            'kategori' => Mitra::whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori'),
        ]);
    }

    public function create()
    {
        return view('master.mitra.form', ['item' => new Mitra(['aktif' => true])]);
    }

    public function store(Request $request)
    {
        Mitra::create($this->validasi($request));
        return redirect()->route('mitra.index')->with('sukses', 'Mitra ditambahkan.');
    }

    public function edit(Mitra $mitra)
    {
        return view('master.mitra.form', ['item' => $mitra]);
    }

    public function update(Request $request, Mitra $mitra)
    {
        $mitra->update($this->validasi($request, $mitra));
        return redirect()->route('mitra.index')->with('sukses', 'Data mitra diperbarui.');
    }

    public function destroy(Mitra $mitra)
    {
        $alasan = null;
        if (Dokumen::withTrashed()->where(fn ($q) => $q->where('mitra_id', $mitra->id)->orWhere('pml_mitra_id', $mitra->id))->exists()) {
            $alasan = 'dokumen SPK/BAST';
        } elseif (\App\Models\Penugasan::where(fn ($q) => $q->where('mitra_id', $mitra->id)->orWhere('pml_mitra_id', $mitra->id))->exists()) {
            $alasan = 'penugasan kegiatan';
        }

        if ($alasan) {
            return back()->with('galat', "Mitra masih terhubung ke {$alasan}. Nonaktifkan saja (hilangkan centang \"Aktif\").");
        }

        $mitra->delete();
        return redirect()->route('mitra.index')->with('sukses', 'Mitra dihapus.');
    }

    private function validasi(Request $request, ?Mitra $mitra = null): array
    {
        $d = $request->validate([
            'id_mitra' => ['required', 'string', 'max:30', Rule::unique('mitra', 'id_mitra')->ignore($mitra)],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'nik' => ['nullable', 'string', 'max:30'],
            'sobat_id' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'posisi' => ['nullable', 'string', 'max:255'],
            'asal' => ['nullable', 'string', 'max:255'],
            'alamat_detail' => ['nullable', 'string', 'max:1000'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', Rule::in(['Lk', 'Pr'])],
            'no_telp' => ['nullable', 'string', 'max:40'],
        ]);
        $d['aktif'] = $request->boolean('aktif');
        return $d;
    }
}
