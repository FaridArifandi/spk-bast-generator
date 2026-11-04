@extends('layouts.app')
@section('judul', 'Verifikasi PPK')
@section('isi')
<div class="tabs">
    @foreach(['menunggu_verifikasi' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $v => $l)
        <a href="{{ route('verifikasi.index', ['status' => $v]) }}" @class(['on' => $status === $v])>{{ $l }} ({{ $hitung[$v] }})</a>
    @endforeach
</div>

<div class="card flush">
    <div class="head"><h2 style="margin:0">SPK petugas</h2></div>
    <div class="scroll"><table class="t">
        <thead><tr><th>Petugas</th><th>Nomor SPK</th><th>Pekerjaan</th><th class="num">Honor</th><th>Keputusan</th></tr></thead>
        <tbody>
        @forelse($dokumen as $d)
            <tr>
                <td><a href="{{ route('dokumen.show', $d) }}">{{ $d->petugas_nama }}</a><div class="muted small">{{ strtoupper($d->peran->value) }} · {{ $d->periode_label }}</div></td>
                <td>{{ $d->no_spk }}</td>
                <td>{{ \Illuminate\Support\Str::limit($d->judul_pekerjaan, 80) }}</td>
                <td class="num">Rp {{ \App\Support\Indonesia::rupiah($d->total_honor) }}</td>
                <td style="min-width:260px">
                    <form method="post" action="{{ route('verifikasi.dokumen', $d) }}">@csrf
                        <input type="text" name="catatan" placeholder="Catatan (wajib bila menolak)" value="{{ $status !== 'menunggu_verifikasi' ? $d->catatan_verifikasi : '' }}" style="margin-bottom:6px">
                        <div class="row"><button class="btn ok sm" name="keputusan" value="disetujui">Setujui</button><button class="btn bad sm" name="keputusan" value="ditolak">Tolak</button></div>
                    </form>
                </td>
            </tr>
        @empty<tr><td colspan="5" class="muted">Tidak ada SPK pada status ini.</td></tr>@endforelse
        </tbody>
    </table></div>{{ $dokumen->links() }}
</div>

<div class="card flush">
    <div class="head"><h2 style="margin:0">BAST Ketua Tim → PPK</h2></div>
    <div class="scroll"><table class="t">
        <thead><tr><th>Nomor</th><th>Uraian</th><th>Ketua tim</th><th class="num">Honor</th><th>Keputusan</th></tr></thead>
        <tbody>
        @forelse($bast as $b)
            <tr>
                <td><a href="{{ route('bast-sm-ppk.show', $b) }}">{{ $b->no_bast }}</a></td>
                <td>{{ \Illuminate\Support\Str::limit($b->uraian_tugas, 80) }}<div class="muted small">{{ $b->bulan }} {{ $b->tahun }}</div></td>
                <td>{{ $b->ketuaTim?->nama }}</td>
                <td class="num">Rp {{ \App\Support\Indonesia::rupiah($b->total_honor) }}</td>
                <td style="min-width:260px">
                    <form method="post" action="{{ route('verifikasi.bast', $b) }}">@csrf
                        <input type="text" name="catatan" placeholder="Catatan (wajib bila menolak)" value="{{ $status !== 'menunggu_verifikasi' ? $b->catatan_verifikasi : '' }}" style="margin-bottom:6px">
                        <div class="row"><button class="btn ok sm" name="keputusan" value="disetujui">Setujui</button><button class="btn bad sm" name="keputusan" value="ditolak">Tolak</button></div>
                    </form>
                </td>
            </tr>
        @empty<tr><td colspan="5" class="muted">Tidak ada BAST pada status ini.</td></tr>@endforelse
        </tbody>
    </table></div>{{ $bast->links() }}
</div>
@endsection
