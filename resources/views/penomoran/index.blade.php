@extends('layouts.app')
@section('title', 'Penomoran')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Buku Nomor Surat <span class="text-muted fs-6">{{ $tahun }}</span></h1>
    <a href="{{ route('penomoran.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Ambil Nomor</a>
</div>

<form class="card card-body border-0 shadow-sm mb-3" method="get">
    <div class="row g-2">
        <div class="col-12 col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nomor atau perihal…"></div>
        <div class="col-6 col-md-2"><select name="tahun" class="form-select">@foreach($tahunList as $t)<option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><select name="jenis" class="form-select"><option value="">Semua jenis</option>@foreach($jenisList as $j)<option value="{{ $j->id }}" @selected(request('jenis') == $j->id)>{{ $j->nama }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><select name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Models\NomorSurat::STATUS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-6 col-md-1"><button class="btn btn-outline-success w-100"><i class="bi bi-search"></i></button></div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nomor</th><th>Jenis</th><th>Perihal</th><th>Pengambil</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($items as $n)
                <tr>
                    <td class="text-nowrap fw-semibold">{{ $n->nomor_lengkap }}</td>
                    <td>{{ $n->jenis->nama }}</td>
                    <td style="min-width:180px">{{ $n->perihal ?: '—' }}
                        @if($n->status === 'batal')<div class="label-sm text-danger">{{ $n->alasan_batal }}</div>@endif</td>
                    <td>{{ $n->pengambil->name }}</td>
                    <td class="text-nowrap">@tgl($n->tanggal)</td>
                    <td><span class="badge text-bg-{{ $n->statusColor() }}">{{ \App\Models\NomorSurat::STATUS[$n->status] }}</span></td>
                    <td class="text-end">
                        @if($n->status === 'dipesan' && (auth()->user()->hasRole('admin','petugas_persuratan') || $n->user_id == auth()->id()))
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#batal-{{ $n->id }}">Batalkan</button>
                        @endif
                    </td>
                </tr>
                @if($n->status === 'dipesan')
                <tr class="collapse" id="batal-{{ $n->id }}"><td colspan="7" class="bg-light">
                    <form method="post" action="{{ route('penomoran.batal', $n) }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        <input name="alasan_batal" class="form-control" style="max-width:420px" placeholder="Alasan pembatalan (wajib)" required>
                        <button class="btn btn-danger">Batalkan nomor</button>
                    </form>
                </td></tr>
                @endif
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada nomor.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
