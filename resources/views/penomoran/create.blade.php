@extends('layouts.app')
@section('title', 'Ambil Nomor')
@section('content')
<h1 class="h4 mb-3">Ambil Nomor Surat</h1>
<form method="post" action="{{ route('penomoran.store') }}" class="card border-0 shadow-sm" style="max-width:820px">
    @csrf
    <div class="card-body">
        <div class="alert alert-info small">
            <span class="badge text-bg-{{ $versi === 'baru' ? 'success' : 'secondary' }} me-1">Format {{ $versi === 'baru' ? 'Perka 2027' : 'Perka 2024' }}</span>
            @if($versi === 'lama')Format Perka 2027 otomatis berlaku mulai {{ \Carbon\Carbon::parse(config('eoffice.format_baru_berlaku'))->translatedFormat('d F Y') }}.@endif
            Nomor dibuat otomatis sesuai Pedoman Tata Naskah Dinas. Nomor yang sudah diambil tidak dapat dipakai orang lain;
            bila tidak jadi dipakai, batalkan agar tercatat di buku nomor (tidak dihapus).
        </div>
        <div class="row g-3">
            @include('partials.nomor-fields', ['withSigner' => true, 'withKategori' => true])
            <div class="col-12">
                <label class="form-label">Perihal / keterangan</label>
                <input name="perihal" value="{{ old('perihal') }}" class="form-control" maxlength="255">
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-success">Ambil nomor</button>
        <a href="{{ route('penomoran.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection
