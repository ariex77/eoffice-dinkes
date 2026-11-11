@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
<h1 class="h4 mb-1">Selamat datang, {{ auth()->user()->name }}</h1>
<p class="text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>

<div class="row g-3 mb-4">
    @php
        $cards = [
            ['Disposisi menunggu Anda', $stat['disposisi_aktif'], 'bi-diagram-3', 'primary', route('disposisi.index')],
            ['Surat masuk bulan ini', $stat['masuk_bulan_ini'], 'bi-inbox', 'success', route('surat-masuk.index')],
            ['Surat keluar bulan ini', $stat['keluar_bulan_ini'], 'bi-send', 'info', route('surat-keluar.index')],
            ['Draf surat keluar', $stat['draf_keluar'], 'bi-pencil-square', 'warning', route('surat-keluar.index', ['status' => 'draft'])],
        ];
        if (!is_null($stat['belum_disposisi'])) {
            array_unshift($cards, ['Surat masuk belum didisposisi', $stat['belum_disposisi'], 'bi-exclamation-circle', 'danger', route('surat-masuk.index', ['status' => 'baru'])]);
        }
    @endphp
    @foreach($cards as [$label, $n, $icon, $color, $url])
        <div class="col-6 col-xl">
            <a href="{{ $url }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="display-6 fw-semibold text-{{ $color }}">{{ $n }}</div>
                            <i class="bi {{ $icon }} fs-3 text-{{ $color }} opacity-50"></i>
                        </div>
                        <div class="text-body-secondary small mt-1">{{ $label }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Disposisi untuk Anda</div>
            <div class="list-group list-group-flush">
                @forelse($disposisi as $d)
                    <a href="{{ route('surat-masuk.show', $d->surat_masuk_id) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $d->suratMasuk->perihal }}</span>
                            <span class="badge text-bg-{{ $d->statusColor() }}">{{ \App\Models\Disposisi::STATUS[$d->status] }}</span>
                        </div>
                        <div class="small text-muted">Dari {{ $d->dari->name }}
                            @if($d->batas_waktu) · batas @tgl($d->batas_waktu) @if($d->terlambat())<span class="text-danger">(terlambat)</span>@endif @endif
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">Tidak ada disposisi yang menunggu.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Surat masuk terbaru</div>
            <div class="list-group list-group-flush">
                @forelse($suratBaru as $s)
                    <a href="{{ route('surat-masuk.show', $s) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $s->perihal }}</span>
                            <span class="badge text-bg-{{ $s->klasifikasiColor() }}">{{ $s->klasifikasi }}</span>
                        </div>
                        <div class="small text-muted">{{ $s->asal_surat }} · @tgl($s->tanggal_terima)</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">Belum ada surat masuk.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
