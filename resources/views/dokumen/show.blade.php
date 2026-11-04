@extends('layouts.app')
@section('judul', 'Dokumen — '.$dokumen->petugas_nama)
@section('aksi')
    @if(auth()->user()->bisaKelolaTim($dokumen->ketua_tim_id))
        <a class="btn" href="{{ route('dokumen.bast', $dokumen) }}">Isi nomor BAST</a>
        <a class="btn" href="{{ route('dokumen.edit', $dokumen) }}">Ubah SPK</a>
        <form method="post" action="{{ route('dokumen.destroy', $dokumen) }}" onsubmit="@if($dokumen->status_verifikasi->value === 'disetujui')if (! confirm('SPK ini SUDAH DISETUJUI PPK. Yakin tetap menghapus? Penugasannya akan dibebaskan kembali dan honornya hilang dari rekap.')) return false; this.konfirmasi.value = 1; return true;@else return confirm('Hapus dokumen ini? Penugasannya akan dibebaskan kembali.')@endif">@csrf @method('delete')<input type="hidden" name="konfirmasi" value="0"><button class="btn bad">Hapus</button></form>
    @endif
@endsection
@section('isi')
<div class="grid c3" style="grid-template-columns:minmax(0,1fr) 320px;align-items:start">
    <div>
        <div class="tabs no-print">
            @foreach($tabs as $t)
                @php($ada = $t === 'spk' || $dokumen->bastTerisi($t))
                <a href="{{ route('dokumen.show', [$dokumen, 'tab' => $t]) }}" @class(['on' => $t === $tab, 'off' => ! $ada])>{{ $jenisLabel[$t] }}@unless($ada) 🔒@endunless</a>
            @endforeach
        </div>

        @if(! $html)
            <div class="alert warn">Nomor &amp; tanggal {{ $jenisLabel[$tab] }} belum diisi.
                @if(auth()->user()->bisaKelolaTim($dokumen->ketua_tim_id))<a href="{{ route('dokumen.bast', $dokumen) }}">Isi sekarang</a>@endif</div>
        @else
            <div class="row no-print" style="margin-bottom:12px">
                @if($boleh)
                    <a class="btn pri" href="{{ route('dokumen.unduh', [$dokumen, $tab]) }}">⬇ Unduh Word (.docx)</a>
                    <button class="btn" type="button" onclick="window.print()">🖨 Cetak</button>
                @else
                    <span class="alert warn" style="margin:0">🔒 {{ $tab === 'spk' ? 'SPK' : 'BAST' }} baru dapat diunduh/dicetak setelah disetujui PPK.</span>
                @endif
            </div>
            <div class="doc-preview-wrapper" style="padding:12px;overflow-x:auto">{!! $html !!}</div>
        @endif
    </div>

    <aside class="no-print">
        <div class="card">
            <h3>Ringkasan</h3>
            <dl class="kv" style="grid-template-columns:100px 1fr">
                <dt>Petugas</dt><dd>{{ $dokumen->petugas_nama }}<div class="muted small">{{ $dokumen->petugas_tipe === 'pegawai' ? 'NIP' : 'NIK' }} {{ $dokumen->petugas_nomor_induk }}</div></dd>
                <dt>Peran</dt><dd>{{ $dokumen->peran->label() }}</dd>
                <dt>PML</dt><dd>{{ $dokumen->pml_label ?: '—' }}</dd>
                <dt>Periode</dt><dd>{{ $dokumen->periode_label }}</dd>
                <dt>No. SPK</dt><dd>{{ $dokumen->no_spk }}</dd>
                <dt>Honor</dt><dd><b>Rp {{ \App\Support\Indonesia::rupiah($dokumen->total_honor) }}</b></dd>
                <dt>PPK</dt><dd>{{ $dokumen->ppk?->nama }}</dd>
                <dt>Ketua tim</dt><dd>{{ $dokumen->ketuaTim?->nama ?? '—' }}</dd>
                <dt>Diunduh</dt><dd>{{ $dokumen->diunduh_at?->translatedFormat('d M Y H:i') ?? 'Belum' }}</dd>
            </dl>
        </div>
        <div class="card">
            <h3>Verifikasi PPK</h3>
            <p><x-status :s="$dokumen->status_verifikasi" /></p>
            @if($dokumen->catatan_verifikasi)<div class="alert {{ $dokumen->status_verifikasi->value === 'ditolak' ? 'bad' : 'ok' }}" style="margin:0 0 10px">{{ $dokumen->catatan_verifikasi }}</div>@endif
            @if($dokumen->verifikator)<p class="muted small">Oleh {{ $dokumen->verifikator->nama }}, {{ $dokumen->tanggal_verifikasi?->translatedFormat('d M Y') }}</p>@endif
            @if(auth()->user()->bisaVerifikasiPpk($dokumen->ppk_id))
                <form method="post" action="{{ route('verifikasi.dokumen', $dokumen) }}">@csrf
                    <div class="field"><textarea name="catatan" rows="2" placeholder="Catatan (wajib bila menolak)"></textarea></div>
                    <div class="row"><button class="btn ok sm" name="keputusan" value="disetujui">Setujui</button><button class="btn bad sm" name="keputusan" value="ditolak">Tolak</button></div>
                </form>
            @endif
        </div>
    </aside>
</div>
@endsection
