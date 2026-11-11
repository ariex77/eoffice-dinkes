@extends('layouts.app')
@section('title', 'Surat Keluar')
@section('content')
@php
    $cek = $surat->checklist ?? [];
    $labelCek = ['cek_nomor' => 'Nomor naskah dinas', 'cek_cap' => 'Cap dinas', 'cek_ttd' => 'Tanda tangan', 'cek_alamat' => 'Alamat yang dituju', 'cek_lampiran' => 'Lampiran'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <a href="{{ route('surat-keluar.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Surat keluar</a>
        <h1 class="h4 mb-0">{{ $surat->perihal }}</h1>
        <div class="text-muted">{{ $surat->nomor->nomor_lengkap }} · {{ $surat->jenis->nama }}</div>
    </div>
    <div class="d-flex gap-2">
        @can('update', $surat)<a href="{{ route('surat-keluar.edit', $surat) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Ubah / unggah berkas</a>@endcan
        @can('delete', $surat)
            <form method="post" action="{{ route('surat-keluar.destroy', $surat) }}" onsubmit="return confirm('Batalkan draf ini? Nomor akan tercatat batal dan tidak dipakai ulang.')">
                @csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Batalkan draf</button>
            </form>
        @endcan
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-{{ $surat->status === 'terkirim' ? 'success' : 'warning' }}">{{ \App\Models\SuratKeluar::STATUS[$surat->status] }}</span>
                    <span class="badge text-bg-{{ $surat->klasifikasiColor() }}">{{ $surat->klasifikasi }} · {{ \App\Models\SuratMasuk::KLASIFIKASI[$surat->klasifikasi] }}</span>
                    <span class="badge text-bg-{{ $surat->sifatColor() }} border">{{ \App\Models\SuratMasuk::SIFAT[$surat->sifat] }}</span>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nomor</dt><dd class="col-sm-8 fw-semibold">{{ $surat->nomor->nomor_lengkap }}</dd>
                    <dt class="col-sm-4">Tanggal surat</dt><dd class="col-sm-8">@tgl($surat->tanggal_surat)</dd>
                    <dt class="col-sm-4">Tujuan</dt><dd class="col-sm-8">{{ $surat->tujuan }}</dd>
                    <dt class="col-sm-4">Isi ringkas</dt><dd class="col-sm-8">{!! nl2br(e($surat->ringkasan ?: '—')) !!}</dd>
                    <dt class="col-sm-4">Lampiran</dt><dd class="col-sm-8">{{ $surat->jumlah_lampiran }}</dd>
                    <dt class="col-sm-4">Penandatangan</dt>
                    <dd class="col-sm-8">
                        @if($surat->mandat){{ \App\Models\SuratKeluar::MANDAT[$surat->mandat] }} — @endif
                        {{ $surat->penandatangan?->name ?? '—' }}
                        @if($surat->dasar_mandat)<div class="label-sm">Dasar pelimpahan: {{ $surat->dasar_mandat }}</div>@endif
                    </dd>
                    <dt class="col-sm-4">Tembusan</dt><dd class="col-sm-8">{!! nl2br(e($surat->tembusan ?: '—')) !!}</dd>
                    <dt class="col-sm-4">Dibuat oleh</dt><dd class="col-sm-8">{{ $surat->pembuat->name }}</dd>
                </dl>
                <hr>
                @if($surat->file_path)
                    <a href="{{ route('surat-keluar.berkas', $surat) }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-eye me-1"></i>Lihat berkas</a>
                    <a href="{{ route('surat-keluar.berkas', [$surat, 'unduh' => 1]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Unduh</a>
                    <span class="small text-muted ms-1">{{ $surat->file_name }}</span>
                @else
                    <span class="text-muted"><i class="bi bi-exclamation-circle me-1"></i>Belum ada pindaian surat.</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if($surat->status === 'terkirim')
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Registrasi pengiriman</div>
                <div class="card-body">
                    <dl class="row mb-2">
                        <dt class="col-5">Agenda keluar</dt><dd class="col-7 fw-semibold">{{ $surat->agendaLabel() }}</dd>
                        <dt class="col-5">Tanggal kirim</dt><dd class="col-7">@tgl($surat->tanggal_kirim)</dd>
                        <dt class="col-5">Metode</dt><dd class="col-7">{{ \App\Models\SuratKeluar::METODE[$surat->metode_kirim] ?? '—' }}</dd>
                        <dt class="col-5">Diregistrasi</dt><dd class="col-7">{{ $surat->peregistrasi?->name }}</dd>
                    </dl>
                    @foreach($cek as $k => $v)<div class="small"><i class="bi bi-check-circle-fill text-success me-1"></i>{{ $labelCek[$k] ?? $k }} diperiksa</div>@endforeach
                </div>
            </div>
        @else
            @can('registrasi', $surat)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Registrasi pengiriman</div>
                    <form method="post" action="{{ route('surat-keluar.registrasi', $surat) }}" class="card-body">
                        @csrf
                        <p class="small text-muted">Periksa kelengkapan sebelum surat dikirim (Pasal 84).</p>
                        @php
                            $items = ['cek_nomor' => 'Nomor naskah dinas sudah benar'];
                            if ($surat->jenis->pakai_cap) $items['cek_cap'] = 'Cap dinas sudah dibubuhkan';
                            $items += ['cek_ttd' => 'Tanda tangan pejabat sudah ada', 'cek_alamat' => 'Alamat yang dituju sudah benar'];
                            if ($surat->jumlah_lampiran > 0) $items['cek_lampiran'] = 'Lampiran lengkap ('.$surat->jumlah_lampiran.')';
                        @endphp
                        @foreach($items as $name => $label)
                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" @checked(old($name))><label class="form-check-label" for="{{ $name }}">{{ $label }}</label></div>
                        @endforeach
                        <div class="row g-2 mt-2">
                            <div class="col-6"><label class="form-label">Tanggal kirim *</label><input type="date" name="tanggal_kirim" class="form-control" value="{{ old('tanggal_kirim', today()->toDateString()) }}" required></div>
                            <div class="col-6"><label class="form-label">Metode *</label>
                                <select name="metode_kirim" class="form-select">@foreach(\App\Models\SuratKeluar::METODE as $k => $v)<option value="{{ $k }}" @selected(old('metode_kirim') === $k)>{{ $v }}</option>@endforeach</select></div>
                        </div>
                        <button class="btn btn-success w-100 mt-3"><i class="bi bi-check2-square me-1"></i>Registrasi & tandai terkirim</button>
                    </form>
                </div>
            @else
                <div class="alert alert-secondary small mb-0">Surat ini masih berstatus draf. Registrasi pengiriman dilakukan oleh Pengadministrasi Persuratan setelah surat ditandatangani dan dipindai.</div>
            @endcan
        @endif
    </div>
</div>
@endsection
