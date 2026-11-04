<?php

namespace App\Http\Controllers;

use App\Enums\StatusVerifikasi;
use App\Models\BastSmPpk;
use App\Models\Dokumen;
use App\Models\DokumenDetail;
use App\Models\Kegiatan;
use App\Models\Pegawai;
use App\Services\DokumenRenderer;
use App\Services\DokumenService;
use App\Services\NomorDokumenService;
use App\Support\Indonesia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BastSmPpkController extends Controller
{
    public function __construct(private NomorDokumenService $nomor, private DokumenRenderer $renderer) {}

    public function index(Request $request)
    {
        $items = BastSmPpk::with(['ketuaTim', 'ppk'])
            ->when($request->tahun, fn ($q, $v) => $q->where('tahun', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status_verifikasi', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('uraian_tugas', 'like', "%$v%")->orWhere('no_bast', 'like', "%$v%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('dokumen.sm-ppk.index', [
            'items' => $items,
            'status' => StatusVerifikasi::cases(),
            'daftarTahun' => range(now()->year + 1, 2024),
        ]);
    }

    public function create()
    {
        $tahun = (int) \App\Models\Parameter::nilai('TAHUN_AKTIF', now()->year);

        return view('dokumen.sm-ppk.form', [
            'bast' => new BastSmPpk(['tahun' => $tahun, 'bulan' => Indonesia::BULAN[now()->month], 'tanggal_bast' => now()]),
            'kegiatan' => Kegiatan::orderBy('kode')->get(),
            'pegawai' => Pegawai::orderBy('nama')->get(),
            'ppkDefault' => app(DokumenService::class)->ppkDefault(),
            'usulan' => $this->nomor->usulan($tahun),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['total_honor'] = $data['total_honor'] ?? $this->hitungHonor($data);
        $data['dibuat_oleh'] = $request->user()->id;
        $data['status_verifikasi'] = StatusVerifikasi::Menunggu;

        $bast = BastSmPpk::create($data);

        return redirect()->route('bast-sm-ppk.show', $bast)->with('sukses', 'BAST SM → PPK dibuat dan diajukan ke PPK.');
    }

    public function show(Request $request, BastSmPpk $bast)
    {
        $bast->load(['ppk', 'ketuaTim', 'verifikator']);

        return view('dokumen.sm-ppk.show', [
            'bast' => $bast,
            'html' => $this->renderer->htmlSmPpk($bast),
            'boleh' => $bast->bolehCetak($request->user()),
        ]);
    }

    public function unduh(Request $request, BastSmPpk $bast)
    {
        if (! $bast->bolehCetak($request->user())) {
            return back()->with('galat', 'Dokumen belum disetujui PPK, sehingga belum dapat diunduh.');
        }
        $berkas = $this->renderer->wordSmPpk($bast);
        $bast->forceFill(['diunduh_at' => now()])->saveQuietly();

        return response()->download($berkas['path'], $berkas['nama'])->deleteFileAfterSend(true);
    }

    public function edit(BastSmPpk $bast)
    {
        return view('dokumen.sm-ppk.form', [
            'bast' => $bast,
            'kegiatan' => Kegiatan::orderBy('kode')->get(),
            'pegawai' => Pegawai::orderBy('nama')->get(),
            'ppkDefault' => null,
            'usulan' => null,
        ]);
    }

    public function update(Request $request, BastSmPpk $bast)
    {
        $data = $this->validasi($request, $bast);
        $data['total_honor'] = $data['total_honor'] ?? $this->hitungHonor($data);

        if (in_array($bast->status_verifikasi, [StatusVerifikasi::Disetujui, StatusVerifikasi::Ditolak], true)) {
            $data['status_verifikasi'] = StatusVerifikasi::Menunggu;
            $data['tanggal_verifikasi'] = null;
            $data['verifikator_id'] = null;
        }
        $bast->update($data);

        return redirect()->route('bast-sm-ppk.show', $bast)->with('sukses', 'BAST SM → PPK diperbarui.');
    }

    public function destroy(BastSmPpk $bast)
    {
        $bast->delete();

        return redirect()->route('bast-sm-ppk.index')->with('sukses', 'BAST SM → PPK dihapus.');
    }

    private function validasi(Request $request, ?BastSmPpk $bast = null): array
    {
        $data = $request->validate([
            'uraian_tugas' => ['required', 'string', 'max:600'],
            'bulan' => ['required', Rule::in(Indonesia::BULAN)],
            'tahun' => ['required', 'integer', 'between:2020,2100'],
            'ketua_tim_id' => ['required', 'exists:pegawai,id'],
            'ppk_id' => ['required', 'exists:pegawai,id'],
            'no_bast' => ['required', 'string', 'max:100'],
            'tanggal_bast' => ['required', 'date'],
            'total_honor' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($this->nomor->pakaiBastSmPpk($data['no_bast'], $bast?->id)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['no_bast' => 'Nomor BAST SM → PPK sudah dipakai.']);
        }

        return $data;
    }

    /** Total honor semua rincian SPK (tidak ditolak) untuk kegiatan & bulan yang sama. */
    private function hitungHonor(array $data): int
    {
        return (int) DokumenDetail::query()
            ->where('uraian_tugas', $data['uraian_tugas'])
            ->whereHas('dokumen', fn ($q) => $q
                ->where('tahun', $data['tahun'])
                ->where('bulan', $data['bulan'])
                ->where('status_verifikasi', '!=', StatusVerifikasi::Ditolak->value)
            )
            ->sum('nilai_perjanjian');
    }
}
