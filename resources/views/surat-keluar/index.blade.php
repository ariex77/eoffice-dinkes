@extends('layouts.app')
@section('title', 'Surat Keluar')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Surat Keluar <span class="text-muted fs-6">{{ $tahun }}</span></h1>
    <a href="{{ route('surat-keluar.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Buat Surat Keluar</a>
</div>

<form class="card card-body border-0 shadow-sm mb-3" method="get">
    <div class="row g-2">
        <div class="col-12 col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nomor, perihal, tujuan…"></div>
        <div class="col-6 col-md-2"><select name="tahun" class="form-select">@foreach($tahunList as $t)<option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><select name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Models\SuratKeluar::STATUS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2"><button class="btn btn-outline-success w-100"><i class="bi bi-search me-1"></i>Cari</button></div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nomor surat</th><th>Tanggal</th><th>Tujuan</th><th>Perihal</th><th>Agenda</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($items as $s)
                <tr>
                    <td class="text-nowrap"><a class="fw-semibold text-decoration-none" href="{{ route('surat-keluar.show', $s) }}">{{ $s->nomor->nomor_lengkap }}</a><div class="label-sm">{{ $s->jenis->nama }}</div></td>
                    <td class="text-nowrap">@tgl($s->tanggal_surat)</td>
                    <td>{{ $s->tujuan }}</td>
                    <td style="min-width:200px">{{ $s->perihal }} @if($s->file_path)<i class="bi bi-paperclip text-muted"></i>@endif</td>
                    <td class="text-nowrap">{{ $s->agendaLabel() ?? '—' }}</td>
                    <td><span class="badge text-bg-{{ $s->status === 'terkirim' ? 'success' : 'warning' }}">{{ \App\Models\SuratKeluar::STATUS[$s->status] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada surat keluar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
