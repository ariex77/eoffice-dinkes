@extends('layouts.app')
@section('title', 'Disposisi')
@section('content')
<h1 class="h4 mb-3">Disposisi</h1>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $tab === 'masuk' ? 'active' : '' }}" href="{{ route('disposisi.index', ['tab' => 'masuk']) }}">Diterima</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'keluar' ? 'active' : '' }}" href="{{ route('disposisi.index', ['tab' => 'keluar']) }}">Diberikan</a></li>
    <li class="nav-item ms-auto">
        @if(request('semua') === '1')
            <a class="nav-link" href="{{ route('disposisi.index', ['tab' => $tab]) }}">Hanya yang aktif</a>
        @else
            <a class="nav-link" href="{{ route('disposisi.index', ['tab' => $tab, 'semua' => 1]) }}">Tampilkan semua</a>
        @endif
    </li>
</ul>

<div class="list-group shadow-sm">
    @forelse($items as $d)
        <a href="{{ route('surat-masuk.show', $d->surat_masuk_id) }}" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between gap-2">
                <div class="fw-semibold">{{ $d->suratMasuk->perihal }}</div>
                <span class="badge text-bg-{{ $d->statusColor() }} align-self-start">{{ \App\Models\Disposisi::STATUS[$d->status] }}</span>
            </div>
            <div class="small text-muted">
                {{ $tab === 'masuk' ? 'Dari '.$d->dari->name : 'Kepada '.$d->kepada->name }}
                · {{ $d->created_at->translatedFormat('d M Y') }}
                @if($d->batas_waktu) · batas @tgl($d->batas_waktu) @if($d->terlambat())<span class="text-danger fw-semibold">terlambat</span>@endif @endif
            </div>
            <div class="mt-1">@foreach($d->instruksi ?? [] as $i)<span class="badge text-bg-light border me-1">{{ $i }}</span>@endforeach</div>
        </a>
    @empty
        <div class="list-group-item text-muted py-4 text-center">Tidak ada disposisi.</div>
    @endforelse
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
