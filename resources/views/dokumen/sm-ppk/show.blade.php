@extends('layouts.app')
@section('judul', 'BAST SM → PPK')
@section('aksi')
    @if(auth()->user()->bisaKelolaTim($bast->ketua_tim_id))
        <a class="btn" href="{{ route('bast-sm-ppk.edit', $bast) }}">Ubah</a>
        <form method="post" action="{{ route('bast-sm-ppk.destroy', $bast) }}" onsubmit="return confirm('Hapus BAST ini?')">@csrf @method('delete')<button class="btn bad">Hapus</button></form>
    @endif
@endsection
@section('isi')
<div class="grid" style="grid-template-columns:minmax(0,1fr) 300px;align-items:start">
    <div>
        <div class="row no-print" style="margin-bottom:12px">
            @if($boleh)
                <a class="btn pri" href="{{ route('bast-sm-ppk.unduh', $bast) }}">⬇ Unduh Word (.docx)</a>
                <button class="btn" type="button" onclick="window.print()">🖨 Cetak</button>
            @else
                <span class="alert warn" style="margin:0">🔒 Dapat diunduh/dicetak setelah disetujui PPK.</span>
            @endif
        </div>
        <div class="doc-preview-wrapper" style="padding:12px;overflow-x:auto">{!! $html !!}</div>
    </div>
    <aside class="no-print">
        <div class="card">
            <h3>Verifikasi PPK</h3>
            <p><x-status :s="$bast->status_verifikasi" /></p>
            @if($bast->catatan_verifikasi)<div class="alert {{ $bast->status_verifikasi->value === 'ditolak' ? 'bad' : 'ok' }}" style="margin:0 0 10px">{{ $bast->catatan_verifikasi }}</div>@endif
            @if($bast->verifikator)<p class="muted small">Oleh {{ $bast->verifikator->nama }}, {{ $bast->tanggal_verifikasi?->translatedFormat('d M Y') }}</p>@endif
            @if(auth()->user()->bisaVerifikasiPpk($bast->ppk_id))
                <form method="post" action="{{ route('verifikasi.bast', $bast) }}">@csrf
                    <div class="field"><textarea name="catatan" rows="2" placeholder="Catatan (wajib bila menolak)"></textarea></div>
                    <div class="row"><button class="btn ok sm" name="keputusan" value="disetujui">Setujui</button><button class="btn bad sm" name="keputusan" value="ditolak">Tolak</button></div>
                </form>
            @endif
        </div>
    </aside>
</div>
@endsection
