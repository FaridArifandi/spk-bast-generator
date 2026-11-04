@extends('layouts.app')
@section('judul', 'Ubah Penugasan')
@section('isi')
<form method="post" action="{{ route('penugasan.update', $penugasan) }}" style="max-width:820px">
    @csrf @method('put')

    <div class="card">
        <h2>1. Kegiatan &amp; periode</h2>
        <x-select name="kegiatan_id" label="Kegiatan" required id="kegiatan" kosong="— pilih kegiatan —">
            @foreach($kegiatan as $k)
                <option value="{{ $k->id }}" data-harga="{{ $k->harga_satuan }}" @selected(old('kegiatan_id', $penugasan->kegiatan_id) == $k->id)>
                    {{ $k->kode }} — {{ $k->uraian_tugas }}
                </option>
            @endforeach
        </x-select>

        <div class="grid c3">
            <x-select name="tipe_periode" label="Jenis periode" required id="tipe">
                @foreach(['bulan' => 'Bulanan', 'triwulan' => 'Triwulan', 'tahap' => 'Tahap', 'subround' => 'Subround', 'semester' => 'Semester'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('tipe_periode', $penugasan->tipe_periode) === $v)>{{ $l }}</option>
                @endforeach
            </x-select>
            <div data-per="bulan"><x-select name="bulan" label="Bulan" kosong="— pilih —">@foreach(\App\Support\Indonesia::BULAN as $b)<option @selected(old('bulan', $penugasan->bulan) === $b)>{{ $b }}</option>@endforeach</x-select></div>
            <div data-per="triwulan" hidden><x-select name="triwulan" label="Triwulan" kosong="—">@foreach([1,2,3,4] as $i)<option @selected(old('triwulan', $penugasan->triwulan) == $i)>{{ $i }}</option>@endforeach</x-select></div>
            <div data-per="tahap" hidden><x-field name="tahap" label="Tahap" :value="old('tahap', $penugasan->tahap)" /></div>
            <div data-per="subround" hidden><x-select name="subround" label="Subround" kosong="—">@foreach([1,2,3] as $i)<option @selected(old('subround', $penugasan->subround) == $i)>{{ $i }}</option>@endforeach</x-select></div>
            <div data-per="semester" hidden><x-select name="semester" label="Semester" kosong="—">@foreach([1,2] as $i)<option @selected(old('semester', $penugasan->semester) == $i)>{{ $i }}</option>@endforeach</x-select></div>
            <x-field name="tahun" label="Tahun" type="number" :value="old('tahun', $penugasan->tahun)" required />
        </div>

        <div class="grid c3">
            <x-field name="tanggal_mulai" label="Tanggal mulai" type="date" :value="old('tanggal_mulai', $penugasan->tanggal_mulai?->format('Y-m-d'))" required />
            <x-field name="tanggal_selesai" label="Tanggal selesai" type="date" :value="old('tanggal_selesai', $penugasan->tanggal_selesai?->format('Y-m-d'))" required />
            <x-field name="batas_terlambat" label="Batas penyerahan" type="date" :value="old('batas_terlambat', $penugasan->batas_terlambat?->format('Y-m-d'))" hint="Kosong = selesai + 5 hari" />
            <x-field name="nomor_kepka" label="Nomor SK/Kepka" :value="old('nomor_kepka', $penugasan->nomor_kepka)" hint="Dasar penugasan di BAST" />
            <x-field name="tanggal_kepka" label="Tanggal SK/Kepka" type="date" :value="old('tanggal_kepka', $penugasan->tanggal_kepka?->format('Y-m-d'))" />
        </div>
    </div>

    <div class="card">
        <h2>2. Data Petugas</h2>
        <div class="grid c2">
            <div class="field">
                <label>Petugas <span style="color:#ef4444">*</span></label>
                <select name="petugas" required style="width:100%">
                    <option value="">— pilih —</option>
                    <optgroup label="Mitra">
                        @foreach($mitra as $m)
                            <option value="mitra:{{ $m->id }}" @selected(old('petugas', $penugasan->petugas_ref) === 'mitra:'.$m->id)>
                                {{ $m->nama }} ({{ $m->id_mitra }})
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Pegawai organik">
                        @foreach($pegawai as $p)
                            <option value="pegawai:{{ $p->id }}" @selected(old('petugas', $penugasan->petugas_ref) === 'pegawai:'.$p->id)>
                                {{ $p->nama }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <x-select name="peran" label="Peran" required>
                @foreach($peran as $v => $l)
                    <option value="{{ $v }}" @selected(old('peran', $penugasan->peran) === $v)>{{ strtoupper($v) }}</option>
                @endforeach
            </x-select>
        </div>

        <div class="grid c3">
            <div class="field">
                <label>PML (untuk PPL/Entri)</label>
                @php($pmlRef = $penugasan->pml_pegawai_id ? 'pegawai:'.$penugasan->pml_pegawai_id : ($penugasan->pml_mitra_id ? 'mitra:'.$penugasan->pml_mitra_id : ''))
                <select name="pml" style="width:100%">
                    <option value="">—</option>
                    <optgroup label="Mitra">
                        @foreach($mitra as $m)
                            <option value="mitra:{{ $m->id }}" @selected(old('pml', $pmlRef) === 'mitra:'.$m->id)>
                                {{ $m->nama }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Pegawai organik">
                        @foreach($pegawai as $p)
                            <option value="pegawai:{{ $p->id }}" @selected(old('pml', $pmlRef) === 'pegawai:'.$p->id)>
                                {{ $p->nama }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <x-field name="volume" label="Volume" type="number" min="1" :value="old('volume', $penugasan->volume)" required />
            <x-field name="harga_satuan" label="Harga satuan (Rp)" type="number" min="0" :value="old('harga_satuan', $penugasan->harga_satuan)" required />
        </div>
    </div>

    <div class="row">
        <button class="btn pri">Simpan Perubahan</button>
        <a class="btn" href="{{ route('penugasan.index') }}">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const tipe = document.getElementById('tipe');
    function periode() {
        document.querySelectorAll('[data-per]').forEach(d => {
            const aktif = d.dataset.per === tipe.value;
            d.hidden = !aktif;
            d.querySelectorAll('select,input').forEach(el => el.disabled = !aktif);
        });
    }
    tipe.addEventListener('change', periode);
    periode();
})();
</script>
@endpush

