@extends('layouts.app')
@section('title', 'Surat Masuk')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Surat Masuk <span class="text-muted fs-6">Buku agenda {{ $tahun }}</span></h1>
    @can('create', \App\Models\SuratMasuk::class)
        <a href="{{ route('surat-masuk.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Catat Surat Masuk</a>
    @endcan
</div>

<form class="card card-body border-0 shadow-sm mb-3" method="get">
    <div class="row g-2">
        <div class="col-12 col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari perihal, nomor, asal surat…"></div>
        <div class="col-6 col-md-2">
            <select name="tahun" class="form-select">@foreach($tahunList as $t)<option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>@endforeach</select>
        </div>
        <div class="col-6 col-md-2">
            <select name="klasifikasi" class="form-select"><option value="">Semua klasifikasi</option>
                @foreach(\App\Models\SuratMasuk::KLASIFIKASI as $k => $v)<option value="{{ $k }}" @selected(request('klasifikasi') === $k)>{{ $k }} — {{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select"><option value="">Semua status</option>
                @foreach(\App\Models\SuratMasuk::STATUS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-2"><button class="btn btn-outline-success w-100"><i class="bi bi-search me-1"></i>Cari</button></div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Agenda</th><th>Diterima</th><th>Surat</th><th>Perihal</th><th>Tujuan</th><th>Klas.</th><th>Status</th></tr>
            </thead>
            <tbody>
            @forelse($items as $s)
                <tr>
                    <td class="text-nowrap"><a href="{{ route('surat-masuk.show', $s) }}" class="fw-semibold text-decoration-none">{{ $s->agendaLabel() }}</a></td>
                    <td class="text-nowrap">@tgl($s->tanggal_terima)</td>
                    <td><div>{{ $s->nomor_surat }}</div><div class="label-sm">{{ $s->asal_surat }} · @tgl($s->tanggal_surat)</div></td>
                    <td style="min-width:220px">
                        <a href="{{ route('surat-masuk.show', $s) }}" class="text-decoration-none text-body">{{ $s->perihal }}</a>
                        @if($s->sifat !== 'biasa')<span class="badge text-bg-{{ $s->sifatColor() }} ms-1">{{ \App\Models\SuratMasuk::SIFAT[$s->sifat] }}</span>@endif
                        @if($s->file_path)<i class="bi bi-paperclip text-muted"></i>@endif
                    </td>
                    <td>{{ $s->unitTujuan?->kode_nomor ?? $s->unitTujuan?->nama ?? '—' }}</td>
                    <td><span class="badge text-bg-{{ $s->klasifikasiColor() }}">{{ $s->klasifikasi }}</span></td>
                    <td><span class="badge text-bg-{{ $s->statusColor() }}">{{ \App\Models\SuratMasuk::STATUS[$s->status] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada surat masuk.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
