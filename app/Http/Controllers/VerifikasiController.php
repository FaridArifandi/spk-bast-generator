<?php

namespace App\Http\Controllers;

use App\Enums\StatusVerifikasi;
use App\Models\BastSmPpk;
use App\Models\Dokumen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VerifikasiController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'menunggu_verifikasi');
        $status = StatusVerifikasi::tryFrom($status)?->value ?? 'menunggu_verifikasi';

        $user = $request->user();
        $milikku = fn ($q) => $this->lingkup($q, $user);

        return view('verifikasi.index', [
            'status' => $status,
            'dokumen' => $milikku(Dokumen::with(['mitra', 'pegawai', 'details']))->where('status_verifikasi', $status)->latest('id')->paginate(15, ['*'], 'hal_spk')->withQueryString(),
            'bast' => $milikku(BastSmPpk::with(['ketuaTim']))->where('status_verifikasi', $status)->latest('id')->paginate(15, ['*'], 'hal_sm')->withQueryString(),
            'hitung' => [
                'menunggu_verifikasi' => $milikku(Dokumen::query())->where('status_verifikasi', 'menunggu_verifikasi')->count() + $milikku(BastSmPpk::query())->where('status_verifikasi', 'menunggu_verifikasi')->count(),
                'disetujui' => $milikku(Dokumen::query())->where('status_verifikasi', 'disetujui')->count() + $milikku(BastSmPpk::query())->where('status_verifikasi', 'disetujui')->count(),
                'ditolak' => $milikku(Dokumen::query())->where('status_verifikasi', 'ditolak')->count() + $milikku(BastSmPpk::query())->where('status_verifikasi', 'ditolak')->count(),
            ],
        ]);
    }

    public function dokumen(Request $request, Dokumen $dokumen)
    {
        $this->putuskan($request, $dokumen);

        return back()->with('sukses', 'Keputusan verifikasi disimpan.');
    }

    public function bastSmPpk(Request $request, BastSmPpk $bast)
    {
        $this->putuskan($request, $bast);

        return back()->with('sukses', 'Keputusan verifikasi disimpan.');
    }

    /** PPK hanya melihat dokumen yang menunjuk dirinya sebagai PPK (ADMIN melihat semuanya). */
    private function lingkup($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(fn ($w) => $w->whereNull('ppk_id')->when($user->pegawai_id, fn ($x) => $x->orWhere('ppk_id', $user->pegawai_id)));
    }

    private function putuskan(Request $request, Dokumen|BastSmPpk $model): void
    {
        abort_unless($request->user()->bisaVerifikasiPpk($model->ppk_id), 403, 'Dokumen ini bukan untuk diverifikasi oleh Anda.');

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan' => [Rule::requiredIf($request->input('keputusan') === 'ditolak'), 'nullable', 'string', 'max:1000'],
        ], ['catatan.required' => 'Alasan penolakan wajib diisi agar operator tahu apa yang perlu diperbaiki.']);

        $model->update([
            'status_verifikasi' => $data['keputusan'],
            'catatan_verifikasi' => $data['catatan'] ?? null,
            'tanggal_verifikasi' => now()->toDateString(),
            'verifikator_id' => $request->user()->id,
        ]);
    }
}
