@extends('layouts.app')
@php $edit = $surat->exists; @endphp
@section('title', $edit ? 'Ubah Surat Masuk' : 'Catat Surat Masuk')
@section('content')
<h1 class="h4 mb-3">{{ $edit ? 'Ubah Surat Masuk '.$surat->agendaLabel() : 'Catat Surat Masuk' }}</h1>

<form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm"
      action="{{ $edit ? route('surat-masuk.update', $surat) : route('surat-masuk.store') }}">
    @csrf @if($edit) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label">Tanggal terima *</label>
                <input type="date" name="tanggal_terima" class="form-control" value="{{ old('tanggal_terima', optional($surat->tanggal_terima)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Tanggal surat *</label>
                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', optional($surat->tanggal_surat)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Nomor surat *</label>
                <input name="nomor_surat" class="form-control" value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Asal surat (instansi/pengirim) *</label>
                <input name="asal_surat" class="form-control" value="{{ old('asal_surat', $surat->asal_surat) }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Unit kerja yang dituju</label>
                <select name="unit_tujuan_id" class="form-select">
                    <option value="">— belum ditentukan —</option>
                    @foreach($units as $u)<option value="{{ $u->id }}" @selected(old('unit_tujuan_id', $surat->unit_tujuan_id) == $u->id)>{{ $u->parent_id ? '— ' : '' }}{{ $u->label() }}</option>@endforeach
                </select>
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
                <select name="klasifikasi" class="form-select">
                    @foreach(\App\Models\SuratMasuk::KLASIFIKASI as $k => $v)<option value="{{ $k }}" @selected(old('klasifikasi', $surat->klasifikasi) === $k)>{{ $k }} — {{ $v }}</option>@endforeach
                </select>
                <div class="form-text">SR/R/T hanya dapat dibuka Kepala Dinas dan pihak yang diberi disposisi.</div>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label">Sifat *</label>
                <select name="sifat" class="form-select">
                    @foreach(\App\Models\SuratMasuk::SIFAT as $k => $v)<option value="{{ $k }}" @selected(old('sifat', $surat->sifat) === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label">Jumlah lampiran</label>
                <input type="number" min="0" name="jumlah_lampiran" class="form-control" value="{{ old('jumlah_lampiran', $surat->jumlah_lampiran ?? 0) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Pindaian surat (PDF/JPG/PNG, maks. {{ config('eoffice.max_upload_kb') / 1024 }} MB)</label>
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                @if($edit && $surat->file_name)<div class="form-text">Berkas saat ini: {{ $surat->file_name }} (unggah baru untuk mengganti).</div>@endif
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-success">Simpan</button>
        <a href="{{ $edit ? route('surat-masuk.show', $surat) : route('surat-masuk.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection
