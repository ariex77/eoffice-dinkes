@extends('layouts.app')
@php $edit = $surat->exists; @endphp
@section('title', $edit ? 'Ubah Draf Surat' : 'Buat Surat Keluar')
@section('content')
<h1 class="h4 mb-3">{{ $edit ? 'Ubah draf: '.$surat->nomor->nomor_lengkap : 'Buat Surat Keluar' }}</h1>

<form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm"
      action="{{ $edit ? route('surat-keluar.update', $surat) : route('surat-keluar.store') }}">
    @csrf @if($edit) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            @unless($edit)
                <div class="col-12">
                    <label class="form-label fw-semibold">Nomor surat</label>
                    <select name="nomor_surat_id" id="nomor_surat_id" class="form-select">
                        <option value="">Buat nomor baru otomatis</option>
                        @foreach($dipesan as $n)<option value="{{ $n->id }}" @selected(old('nomor_surat_id') == $n->id)>Pakai nomor yang sudah dipesan: {{ $n->nomor_lengkap }} ({{ $n->jenis->nama }})</option>@endforeach
                    </select>
                </div>
                <div class="col-12" id="blok-nomor-baru"><div class="row g-3">
                    @include('partials.nomor-fields', ['withSigner' => false, 'withKategori' => false])
                </div></div>
            @endunless

            <div class="col-6 col-md-3">
                <label class="form-label">Tanggal surat *</label>
                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', optional($surat->tanggal_surat)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-12 col-md-9">
                <label class="form-label">Tujuan (Yth.) *</label>
                <input name="tujuan" class="form-control" value="{{ old('tujuan', $surat->tujuan) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Perihal *</label>
                <input name="perihal" class="form-control" value="{{ old('perihal', $surat->perihal) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Isi ringkas</label>
                <textarea name="ringkasan" rows="3" class="form-control">{{ old('ringkasan', $surat->ringkasan) }}</textarea>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Klasifikasi keamanan *</label>
                <select name="klasifikasi" class="form-select">@foreach(\App\Models\SuratMasuk::KLASIFIKASI as $k => $v)<option value="{{ $k }}" @selected(old('klasifikasi', $surat->klasifikasi) === $k)>{{ $k }} — {{ $v }}</option>@endforeach</select>
                @unless($edit)<div class="form-text">Pada format Perka 2027, untuk Surat Dinas dan turunannya klasifikasi ini menjadi awalan nomor (B/…, T/…).</div>@endunless
                @if($edit && $surat->nomor->kategori)<div class="form-text">Dikunci: kategori {{ $surat->nomor->kategori }} sudah menjadi bagian nomor surat.</div>@endif
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label">Sifat *</label>
                <select name="sifat" class="form-select">@foreach(\App\Models\SuratMasuk::SIFAT as $k => $v)<option value="{{ $k }}" @selected(old('sifat', $surat->sifat) === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label">Jumlah lampiran</label>
                <input type="number" min="0" name="jumlah_lampiran" class="form-control" value="{{ old('jumlah_lampiran', $surat->jumlah_lampiran ?? 0) }}">
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label">Penandatangan</label>
                <select name="penandatangan_id" class="form-select"><option value="">—</option>
                    @foreach($signers as $s)<option value="{{ $s->id }}" @selected(old('penandatangan_id', $surat->penandatangan_id) == $s->id)>{{ $s->name }} — {{ $s->jabatan }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Pelimpahan wewenang</label>
                <select name="mandat" class="form-select"><option value="">Tidak ada (langsung)</option>
                    @foreach(\App\Models\SuratKeluar::MANDAT as $k => $v)<option value="{{ $k }}" @selected(old('mandat', $surat->mandat) === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Dasar pelimpahan (tertulis)</label>
                <input name="dasar_mandat" class="form-control" placeholder="Nomor SK / surat pelimpahan" value="{{ old('dasar_mandat', $surat->dasar_mandat) }}">
            </div>
            <div class="col-12"><label class="form-label">Tembusan</label><textarea name="tembusan" rows="2" class="form-control">{{ old('tembusan', $surat->tembusan) }}</textarea></div>
            <div class="col-12">
                <label class="form-label">Pindaian surat bertanda tangan / bercap (PDF/JPG/PNG)</label>
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                <div class="form-text">Dapat diunggah belakangan setelah surat ditandatangani, tetapi wajib ada sebelum registrasi pengiriman.
                    @if($edit && $surat->file_name) Berkas saat ini: {{ $surat->file_name }}.@endif</div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-success">{{ $edit ? 'Simpan perubahan' : 'Simpan draf' }}</button>
        <a href="{{ $edit ? route('surat-keluar.show', $surat) : route('surat-keluar.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@unless($edit)
@push('scripts')
<script>
(function () {
    const pilih = document.getElementById('nomor_surat_id'), blok = document.getElementById('blok-nomor-baru');
    const sync = () => blok.classList.toggle('d-none', pilih.value !== '');
    pilih.addEventListener('change', sync); sync();
})();
</script>
@endpush
@endunless
