@extends('layouts.app')
@section('judul', 'Tugaskan petugas ke kegiatan')
@section('isi')
<form method="post" action="{{ route('penugasan.store') }}" id="form-penugasan">
    @csrf
    <div class="card">
        <h2>1. Kegiatan &amp; periode</h2>
        <x-select name="kegiatan_id" label="Kegiatan" required id="kegiatan" kosong="— pilih kegiatan —">
            @foreach($kegiatan as $k)<option value="{{ $k->id }}" data-harga="{{ $k->harga_satuan }}" @selected(old('kegiatan_id') == $k->id)>{{ $k->kode }} — {{ $k->uraian_tugas }}</option>@endforeach
        </x-select>
        <div class="grid c3">
            <x-select name="tipe_periode" label="Jenis periode" required id="tipe">
                @foreach(['bulan' => 'Bulanan', 'triwulan' => 'Triwulan', 'tahap' => 'Tahap', 'subround' => 'Subround', 'semester' => 'Semester'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('tipe_periode', 'bulan') === $v)>{{ $l }}</option>@endforeach
            </x-select>
            <div data-per="bulan"><x-select name="bulan" label="Bulan" kosong="— pilih —">@foreach(\App\Support\Indonesia::BULAN as $b)<option @selected(old('bulan', \App\Support\Indonesia::BULAN[now()->month]) === $b)>{{ $b }}</option>@endforeach</x-select></div>
            <div data-per="triwulan" hidden><x-select name="triwulan" label="Triwulan" kosong="—">@foreach([1,2,3,4] as $i)<option @selected(old('triwulan') == $i)>{{ $i }}</option>@endforeach</x-select></div>
            <div data-per="tahap" hidden><x-field name="tahap" label="Tahap" :value="old('tahap')" /></div>
            <div data-per="subround" hidden><x-select name="subround" label="Subround" kosong="—">@foreach([1,2,3] as $i)<option @selected(old('subround') == $i)>{{ $i }}</option>@endforeach</x-select></div>
            <div data-per="semester" hidden><x-select name="semester" label="Semester" kosong="—">@foreach([1,2] as $i)<option @selected(old('semester') == $i)>{{ $i }}</option>@endforeach</x-select></div>
            <x-field name="tahun" label="Tahun" type="number" :value="old('tahun', \App\Models\Parameter::nilai('TAHUN_AKTIF', now()->year))" required />
        </div>
        <div class="grid c3">
            <x-field name="tanggal_mulai" label="Tanggal mulai" type="date" required />
            <x-field name="tanggal_selesai" label="Tanggal selesai" type="date" required />
            <x-field name="batas_terlambat" label="Batas penyerahan" type="date" hint="Kosong = selesai + 5 hari" />
            <x-field name="nomor_kepka" label="Nomor SK/Kepka" hint="Dasar penugasan di BAST" />
            <x-field name="tanggal_kepka" label="Tanggal SK/Kepka" type="date" />
        </div>
    </div>

    <div class="card">
        <div class="head" style="padding:0 0 10px;border:0"><h2 style="margin:0">2. Petugas</h2>
            <button type="button" class="btn sm" id="tambah">+ Tambah petugas</button></div>
        @error('rows')<div class="err">{{ $message }}</div>@enderror
        <div class="scroll"><table class="t" id="tabel-petugas">
            <thead><tr><th>Petugas</th><th>Peran</th><th>PML (untuk PPL/Entri)</th><th style="width:90px">Volume</th><th style="width:130px">Harga satuan</th><th></th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="muted small">Honor per baris = volume × harga satuan. Harga terisi otomatis dari kegiatan, tetap bisa diubah.</p>
    </div>

    <div class="row"><button class="btn pri">Simpan penugasan</button><a class="btn" href="{{ route('penugasan.index') }}">Batal</a></div>
</form>

<template id="baris">
    <tr>
        <td><select data-n="petugas" required style="min-width:200px">
            <option value="">— pilih —</option>
            <optgroup label="Mitra">@foreach($mitra as $m)<option value="mitra:{{ $m->id }}">{{ $m->nama }} ({{ $m->id_mitra }})</option>@endforeach</optgroup>
            <optgroup label="Pegawai organik">@foreach($pegawai as $p)<option value="pegawai:{{ $p->id }}">{{ $p->nama }}</option>@endforeach</optgroup>
        </select></td>
        <td><select data-n="peran">@foreach($peran as $v => $l)<option value="{{ $v }}">{{ strtoupper($v) }}</option>@endforeach</select></td>
        <td><select data-n="pml" style="min-width:180px">
            <option value="">—</option>
            <optgroup label="Mitra">@foreach($mitra as $m)<option value="mitra:{{ $m->id }}">{{ $m->nama }}</option>@endforeach</optgroup>
            <optgroup label="Pegawai organik">@foreach($pegawai as $p)<option value="pegawai:{{ $p->id }}">{{ $p->nama }}</option>@endforeach</optgroup>
        </select></td>
        <td><input type="number" data-n="volume" min="1" value="1" required></td>
        <td><input type="number" data-n="harga_satuan" min="0" value="0" required></td>
        <td><button type="button" class="btn sm bad hapus">×</button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
(function () {
    const tbody = document.querySelector('#tabel-petugas tbody');
    const tpl = document.getElementById('baris');
    const kegiatan = document.getElementById('kegiatan');
    let i = 0;

    function harga() { return kegiatan.selectedOptions[0]?.dataset.harga || 0; }

    function tambah(nilai) {
        const tr = tpl.content.firstElementChild.cloneNode(true);
        tr.querySelectorAll('[data-n]').forEach(el => { el.name = `rows[${i}][${el.dataset.n}]`; });
        tr.querySelector('[data-n=harga_satuan]').value = harga();
        tr.querySelector('.hapus').onclick = () => tr.remove();
        tbody.appendChild(tr); i++;
    }

    document.getElementById('tambah').onclick = () => tambah();
    kegiatan.addEventListener('change', () => {
        tbody.querySelectorAll('[data-n=harga_satuan]').forEach(el => el.value = harga());
    });

    // jenis periode → tampilkan kolom yang sesuai
    const tipe = document.getElementById('tipe');
    function periode() {
        document.querySelectorAll('[data-per]').forEach(d => {
            const aktif = d.dataset.per === tipe.value;
            d.hidden = !aktif;
            d.querySelectorAll('select,input').forEach(el => el.disabled = !aktif);
        });
    }
    tipe.addEventListener('change', periode); periode();

    // pulihkan baris setelah validasi gagal
    const lama = @json(old('rows', []));
    if (lama.length) {
        lama.forEach(r => { tambah(); const tr = tbody.lastElementChild;
            Object.entries(r).forEach(([k, v]) => { const el = tr.querySelector(`[data-n=${k}]`); if (el) el.value = v; }); });
    } else { tambah(); }
})();
</script>
@endpush
