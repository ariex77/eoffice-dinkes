@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Pengguna</h1>
    <a href="{{ route('users.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Tambah Pengguna</a>
</div>
<form class="mb-3" method="get"><input name="q" value="{{ request('q') }}" class="form-control" style="max-width:360px" placeholder="Cari nama, email, atau NIP…"></form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Peran</th><th>Unit</th><th>Kode jabatan</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td><div class="fw-semibold">{{ $u->name }}</div><div class="label-sm">{{ $u->email }}@if($u->nip) · {{ $u->nip }}@endif</div></td>
                    <td>{{ $u->roleLabel() }}</td>
                    <td>{{ $u->unit?->kode_nomor ?? $u->unit?->nama ?? '—' }}</td>
                    <td>{{ $u->kode_jabatan ?? '—' }}</td>
                    <td><span class="badge text-bg-{{ $u->is_active ? 'success' : 'secondary' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-end"><a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary">Ubah</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
