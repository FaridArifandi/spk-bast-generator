<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusVerifikasi;
use App\Models\Dokumen;
use App\Models\Pegawai;
use App\Models\Penugasan;
use App\Services\DokumenRenderer;
use App\Services\DokumenService;
use App\Services\NomorDokumenService;
use App\Support\Indonesia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class DokumenController extends Controller
{
    public function __construct(
        private DokumenService $service,
        private NomorDokumenService $nomor,
        private DokumenRenderer $renderer,
    ) {}

    public function index(Request $request)
    {
        $items = Dokumen::with(['mitra', 'pegawai'])
            ->cari($request->q)
            ->when($request->tahun, fn ($q, $v) => $q->where('tahun', $v))
            ->when($request->bulan, fn ($q, $v) => $q->where('bulan', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status_verifikasi', $v))
            ->when($request->peran, fn ($q, $v) => $q->where('peran', $v))
            ->latest('id')->paginate(20)->withQueryString();

        return view('dokumen.index', [
            'items' => $items,
            'status' => StatusVerifikasi::cases(),
            'peran' => Peran::options(),
            'daftarTahun' => range(now()->year + 1, 2024),
        ]);
    }

    /** Langkah 1: pilih petugas; langkah 2: pilih penugasan yang akan dimasukkan ke SPK. */
    public function create(Request $request)
    {
        $tersedia = Penugasan::belumDipakai()->with(['mitra', 'pegawai', 'kegiatan', 'pmlMitra', 'pmlPegawai'])->get();

        $petugasList = $tersedia->groupBy('petugas_ref')->map(fn ($g) => (object) [
            'ref' => $g->first()->petugas_ref,
            'nama' => $g->first()->petugas_nama,
            'jumlah' => $g->count(),
        ])->sortBy('nama')->values();

        $ref = $request->query('petugas');
        $penugasan = $ref ? $tersedia->where('petugas_ref', $ref)->values() : collect();

        $tahun = (int) ($penugasan->first()->tahun ?? \App\Models\Parameter::nilai('TAHUN_AKTIF', now()->year));

        return view('dokumen.create', [
            'petugasList' => $petugasList,
            'ref' => $ref,
            'penugasan' => $penugasan,
            'pegawai' => Pegawai::orderBy('nama')->get(),
            'ppkDefault' => $this->service->ppkDefault(),
            'usulan' => $this->nomor->usulan($tahun),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'penugasan_ids' => ['required', 'array', 'min:1'],
            'penugasan_ids.*' => ['integer', 'exists:penugasan,id'],
            'no_spk' => ['required', 'string', 'max:100'],
            'tanggal_spk' => ['required', 'date'],
            'ppk_id' => ['required', 'exists:pegawai,id'],
            'ketua_tim_id' => ['nullable', 'exists:pegawai,id'],
            'judul_pekerjaan' => ['nullable', 'string', 'max:600'],
            'nomor_kepka' => ['nullable', 'string', 'max:255'],
            'tanggal_kepka' => ['nullable', 'date'],
        ], ['penugasan_ids.required' => 'Pilih minimal satu penugasan.']);

        if ($this->nomor->pakaiSpk($data['no_spk'])) {
            return back()->withInput()->withErrors(['no_spk' => 'Nomor SPK sudah dipakai dokumen lain.']);
        }

        $penugasan = Penugasan::whereIn('id', $data['penugasan_ids'])->get();

        try {
            $dokumen = $this->service->buatDariPenugasan($penugasan, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['penugasan_ids' => $e->getMessage()]);
        }

        return redirect()->route('dokumen.show', $dokumen)->with('sukses', 'SPK dibuat dan diajukan ke PPK untuk diverifikasi.');
    }

    public function show(Request $request, Dokumen $dokumen)
    {
        $dokumen->load(['mitra', 'pegawai', 'ppk', 'ketuaTim', 'verifikator', 'details']);

        $tabs = array_merge(['spk'], $dokumen->jenisBast());
        $tab = $request->query('tab', 'spk');
        abort_unless(in_array($tab, $tabs, true), 404);

        $tersedia = $tab === 'spk' || $dokumen->bastTerisi($tab);

        return view('dokumen.show', [
            'dokumen' => $dokumen,
            'tabs' => $tabs,
            'tab' => $tab,
            'jenisLabel' => DokumenRenderer::JENIS,
            'html' => $tersedia ? $this->renderer->html($dokumen, $tab) : null,
            'boleh' => $dokumen->bolehCetak($tab, $request->user()),
        ]);
    }

    public function unduh(Request $request, Dokumen $dokumen, string $jenis)
    {
        abort_unless(in_array($jenis, array_merge(['spk'], $dokumen->jenisBast()), true), 404);

        if (! $dokumen->bolehCetak($jenis, $request->user())) {
            return back()->with('galat', 'Dokumen belum disetujui PPK, sehingga belum dapat diunduh.');
        }
        if ($jenis !== 'spk' && ! $dokumen->bastTerisi($jenis)) {
            return back()->with('galat', 'Nomor & tanggal BAST belum diisi.');
        }

        $berkas = $this->renderer->word($dokumen, $jenis);
        $dokumen->forceFill(['diunduh_at' => now()])->saveQuietly();

        return response()->download($berkas['path'], $berkas['nama'])->deleteFileAfterSend(true);
    }

    public function unduhZip(Request $request)
    {
        $tahun = (int) $request->input('tahun', (int) \App\Models\Parameter::nilai('TAHUN_AKTIF', now()->year));
        $bulan = $request->input('bulan');
        if ($bulan === 'semua' || ! $bulan) {
            $bulan = null;
        }

        $query = Dokumen::with(['mitra', 'pegawai', 'ppk', 'ketuaTim', 'details'])
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->where('status_verifikasi', StatusVerifikasi::Disetujui);

        $dokumenList = $query->get();

        if ($dokumenList->isEmpty()) {
            return back()->with('galat', 'Tidak ada dokumen yang disetujui pada periode ini untuk diunduh.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'spk_zip_') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('galat', 'Gagal membuat berkas ZIP.');
        }

        $fileTemp = [];

        foreach ($dokumenList as $d) {
            // SPK Word
            $spk = $this->renderer->word($d, 'spk');
            $fileTemp[] = $spk['path'];
            $zip->addFile($spk['path'], 'SPK/' . $spk['nama']);

            // BAST Word yang sudah terisi nomornya
            foreach ($d->jenisBast() as $j) {
                if ($d->bastTerisi($j)) {
                    $bast = $this->renderer->word($d, $j);
                    $fileTemp[] = $bast['path'];
                    $zip->addFile($bast['path'], 'BAST/' . $bast['nama']);
                }
            }
            $d->forceFill(['diunduh_at' => now()])->saveQuietly();
        }

        $zip->close();

        foreach ($fileTemp as $f) {
            @unlink($f);
        }

        $namaZip = 'Dokumen_' . ($bulan ?: 'Semua') . "_{$tahun}.zip";

        return response()->download($zipPath, $namaZip)->deleteFileAfterSend(true);
    }

    public function edit(Dokumen $dokumen)
    {
        $dokumen->load(['mitra', 'pegawai', 'details']);

        return view('dokumen.edit', [
            'dokumen' => $dokumen,
            'pegawai' => Pegawai::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Dokumen $dokumen)
    {
        $data = $request->validate([
            'no_spk' => ['required', 'string', 'max:100'],
            'tanggal_spk' => ['required', 'date'],
            'ppk_id' => ['required', 'exists:pegawai,id'],
            'ketua_tim_id' => ['nullable', 'exists:pegawai,id'],
            'judul_pekerjaan' => ['required', 'string', 'max:600'],
            'nomor_kepka' => ['nullable', 'string', 'max:255'],
            'tanggal_kepka' => ['nullable', 'date'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'batas_penyerahan' => ['nullable', 'date'],
            'details' => ['nullable', 'array'],
            'details.*.volume' => ['required', 'integer', 'min:1'],
            'details.*.harga_satuan' => ['required', 'integer', 'min:0'],
        ]);

        if ($this->nomor->pakaiSpk($data['no_spk'], $dokumen->id)) {
            return back()->withInput()->withErrors(['no_spk' => 'Nomor SPK sudah dipakai dokumen lain.']);
        }

        $this->service->perbarui($dokumen, $data);

        return redirect()->route('dokumen.show', $dokumen)->with('sukses', 'SPK diperbarui. Status verifikasi dikembalikan ke menunggu bila sebelumnya sudah diputuskan.');
    }

    public function destroy(Dokumen $dokumen)
    {
        $this->service->hapus($dokumen);

        return redirect()->route('dokumen.index')->with('sukses', 'Dokumen dihapus. Penugasannya bisa dibuatkan SPK kembali.');
    }

    /** Form pengisian nomor & tanggal BAST (tidak memengaruhi status verifikasi SPK). */
    public function bast(Dokumen $dokumen)
    {
        $dokumen->load(['mitra', 'pegawai']);
        $usulan = $this->nomor->usulan((int) $dokumen->tahun);
        $urut = $usulan['bast_urut'];

        $baris = [];
        foreach ($dokumen->jenisBast() as $jenis) {
            $kolom = str_replace('-', '_', $jenis);
            $baris[] = [
                'judul' => DokumenRenderer::JENIS[$jenis],
                'no_field' => "no_bast_$kolom",
                'tgl_field' => "tanggal_bast_$kolom",
                'no' => $dokumen->{"no_bast_$kolom"} ?: $this->nomor->formatBast($urut++, (int) $dokumen->tahun),
                'tanggal' => ($dokumen->{"tanggal_bast_$kolom"} ?? $dokumen->tanggal_selesai)?->format('Y-m-d'),
            ];
        }

        return view('dokumen.bast', ['dokumen' => $dokumen, 'usulan' => $usulan, 'baris' => $baris]);
    }

    public function bastSimpan(Request $request, Dokumen $dokumen)
    {
        $rules = [];
        foreach ($dokumen->jenisBast() as $jenis) {
            $kolom = str_replace('-', '_', $jenis);
            $rules["no_bast_$kolom"] = ['required', 'string', 'max:100'];
            $rules["tanggal_bast_$kolom"] = ['required', 'date'];
        }
        $data = $request->validate($rules);

        // nomor BAST tidak boleh kembar, baik dengan dokumen lain maupun sesama field di dokumen ini
        $dipakaiSendiri = [];
        foreach ($dokumen->jenisBast() as $jenis) {
            $kolom = 'no_bast_'.str_replace('-', '_', $jenis);
            $nilai = $data[$kolom];
            $norm = $this->nomor->normal($nilai);
            if (in_array($norm, $dipakaiSendiri, true) || $this->nomor->pakaiBast($nilai, $dokumen->id)) {
                return back()->withInput()->withErrors([$kolom => 'Nomor BAST sudah dipakai.']);
            }
            $dipakaiSendiri[] = $norm;
        }

        $dokumen->update($data);

        return redirect()->route('dokumen.show', $dokumen)->with('sukses', 'Data BAST disimpan.');
    }
}
