<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\Pegawai;
use App\Models\Penugasan;
use App\Services\PenugasanService;
use App\Support\Indonesia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenugasanController extends Controller
{
    public function index(Request $request)
    {
        $items = Penugasan::with(['kegiatan', 'mitra', 'pegawai', 'pmlMitra', 'pmlPegawai'])
            ->when($request->kegiatan_id, fn ($q, $v) => $q->where('kegiatan_id', $v))
            ->when($request->tahun, fn ($q, $v) => $q->where('tahun', $v))
            ->when($request->status === 'belum', fn ($q) => $q->whereNull('dokumen_id'))
            ->when($request->status === 'sudah', fn ($q) => $q->whereNotNull('dokumen_id'))
            ->when($request->q, function ($q, $kata) {
                $q->where(fn ($w) => $w->whereHas('mitra', fn ($m) => $m->where('nama', 'like', "%$kata%"))
                    ->orWhereHas('pegawai', fn ($m) => $m->where('nama', 'like', "%$kata%")));
            })
            ->latest('id')->paginate(25)->withQueryString();

        return view('penugasan.index', [
            'items' => $items,
            'kegiatan' => Kegiatan::orderBy('kode')->get(['id', 'kode', 'uraian_tugas']),
        ]);
    }

    public function create()
    {
        return view('penugasan.create', [
            'kegiatan' => Kegiatan::orderBy('kode')->get(),
            'mitra' => Mitra::where('aktif', true)->orderBy('nama')->get(['id', 'nama', 'id_mitra', 'posisi']),
            'pegawai' => Pegawai::orderBy('nama')->get(['id', 'nama', 'jabatan']),
            'peran' => Peran::options(),
        ]);
    }

    public function store(Request $request, PenugasanService $service)
    {
        $tipe = $request->input('tipe_periode', 'bulan');

        $data = $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'tipe_periode' => ['required', Rule::in(['bulan', 'triwulan', 'tahap', 'subround', 'semester'])],
            'bulan' => [Rule::requiredIf($tipe === 'bulan'), 'nullable', Rule::in(Indonesia::BULAN)],
            'triwulan' => [Rule::requiredIf($tipe === 'triwulan'), 'nullable', 'integer', 'between:1,4'],
            'tahap' => [Rule::requiredIf($tipe === 'tahap'), 'nullable', 'string', 'max:10'],
            'subround' => [Rule::requiredIf($tipe === 'subround'), 'nullable', 'integer', 'between:1,3'],
            'semester' => [Rule::requiredIf($tipe === 'semester'), 'nullable', 'integer', 'between:1,2'],
            'tahun' => ['required', 'integer', 'between:2020,2100'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'batas_terlambat' => ['nullable', 'date'],
            'nomor_kepka' => ['nullable', 'string', 'max:255'],
            'tanggal_kepka' => ['nullable', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.petugas' => ['required', 'regex:/^(mitra|pegawai):\d+$/'],
            'rows.*.peran' => ['required', Rule::in(array_keys(Peran::options()))],
            'rows.*.pml' => ['nullable', 'regex:/^(mitra|pegawai):\d+$/'],
            'rows.*.volume' => ['required', 'integer', 'min:1'],
            'rows.*.harga_satuan' => ['required', 'integer', 'min:0'],
        ]);

        $n = $service->simpan($data, $data['rows'], $request->user());

        return redirect()->route('penugasan.index')->with('sukses', "$n penugasan tersimpan.");
    }

    public function edit(Penugasan $penugasan)
    {
        if ($penugasan->dokumen_id) {
            return redirect()->route('penugasan.index')->with('galat', 'Penugasan sudah dipakai di SPK. Ubah rinciannya langsung lewat menu Ubah SPK.');
        }

        $penugasan->load(['kegiatan', 'mitra', 'pegawai', 'pmlMitra', 'pmlPegawai']);

        return view('penugasan.edit', [
            'penugasan' => $penugasan,
            'kegiatan' => Kegiatan::orderBy('kode')->get(),
            'mitra' => Mitra::where('aktif', true)->orderBy('nama')->get(['id', 'nama', 'id_mitra', 'posisi']),
            'pegawai' => Pegawai::orderBy('nama')->get(['id', 'nama', 'jabatan']),
            'peran' => Peran::options(),
        ]);
    }

    public function update(Request $request, Penugasan $penugasan)
    {
        if ($penugasan->dokumen_id) {
            return redirect()->route('penugasan.index')->with('galat', 'Penugasan sudah dipakai di SPK. Ubah rinciannya langsung lewat menu Ubah SPK.');
        }

        $tipe = $request->input('tipe_periode', 'bulan');

        $data = $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'tipe_periode' => ['required', Rule::in(['bulan', 'triwulan', 'tahap', 'subround', 'semester'])],
            'bulan' => [Rule::requiredIf($tipe === 'bulan'), 'nullable', Rule::in(Indonesia::BULAN)],
            'triwulan' => [Rule::requiredIf($tipe === 'triwulan'), 'nullable', 'integer', 'between:1,4'],
            'tahap' => [Rule::requiredIf($tipe === 'tahap'), 'nullable', 'string', 'max:10'],
            'subround' => [Rule::requiredIf($tipe === 'subround'), 'nullable', 'integer', 'between:1,3'],
            'semester' => [Rule::requiredIf($tipe === 'semester'), 'nullable', 'integer', 'between:1,2'],
            'tahun' => ['required', 'integer', 'between:2020,2100'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'batas_terlambat' => ['nullable', 'date'],
            'nomor_kepka' => ['nullable', 'string', 'max:255'],
            'tanggal_kepka' => ['nullable', 'date'],
            'petugas' => ['required', 'regex:/^(mitra|pegawai):\d+$/'],
            'peran' => ['required', Rule::in(array_keys(Peran::options()))],
            'pml' => ['nullable', 'regex:/^(mitra|pegawai):\d+$/'],
            'volume' => ['required', 'integer', 'min:1'],
            'harga_satuan' => ['required', 'integer', 'min:0'],
        ]);

        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);
        $mulai = Indonesia::carbon($data['tanggal_mulai'] ?? null);

        $periode = [
            'tipe_periode' => $tipe,
            'bulan' => null, 'triwulan' => null, 'tahap' => null, 'subround' => null, 'semester' => null,
        ];
        $periode[$tipe] = $data[$tipe] ?? null;
        if (empty($periode['bulan']) && $mulai) {
            $periode['bulan'] = Indonesia::BULAN[$mulai->month];
        }

        $batas = Indonesia::carbon($data['batas_terlambat'] ?? null)
            ?? Indonesia::carbon($data['tanggal_selesai'] ?? null)?->copy()->addDays(5);

        $penugasan->setPetugasDari($data['petugas']);
        $penugasan->setPmlDari($data['pml'] ?? null);
        $penugasan->kegiatan_id = $kegiatan->id;
        $penugasan->peran = $data['peran'];
        $penugasan->tanggal_mulai = $data['tanggal_mulai'];
        $penugasan->tanggal_selesai = $data['tanggal_selesai'];
        $penugasan->batas_terlambat = $batas?->toDateString();
        $penugasan->nomor_kepka = $data['nomor_kepka'] ?? null;
        $penugasan->tanggal_kepka = $data['tanggal_kepka'] ?? null;
        $penugasan->forceFill($periode);
        $penugasan->tahun = (int) $data['tahun'];

        $volume = max(1, (int) $data['volume']);
        $harga = (int) $data['harga_satuan'];
        $penugasan->volume = $volume;
        $penugasan->satuan = $kegiatan->satuan ?: 'Dokumen';
        $penugasan->harga_satuan = $harga;
        $penugasan->total_honor = $volume * $harga;
        $penugasan->save();

        return redirect()->route('penugasan.index')->with('sukses', 'Penugasan diperbarui.');
    }

    public function destroy(Penugasan $penugasan)
    {
        if ($penugasan->dokumen_id) {
            return back()->with('galat', 'Penugasan sudah dipakai di SPK. Hapus/ubah SPK-nya lebih dulu.');
        }
        $penugasan->delete();

        return back()->with('sukses', 'Penugasan dihapus.');
    }
}
