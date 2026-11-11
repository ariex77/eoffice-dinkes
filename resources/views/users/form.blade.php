@extends('layouts.app')
@php $edit = $user->exists; @endphp
@section('title', $edit ? 'Ubah Pengguna' : 'Tambah Pengguna')
@section('content')
<h1 class="h4 mb-3">{{ $edit ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</h1>
<form method="post" action="{{ $edit ? route('users.update', $user) : route('users.store') }}" class="card border-0 shadow-sm" style="max-width:820px">
    @csrf @if($edit) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6"><label class="form-label">Nama lengkap *</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
            <div class="col-12 col-md-6"><label class="form-label">NIP</label><input name="nip" class="form-control" value="{{ old('nip', $user->nip) }}"></div>
            <div class="col-12 col-md-6"><label class="form-label">Email (untuk login) *</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
            <div class="col-12 col-md-6">
                <label class="form-label">Kata sandi {{ $edit ? '(kosongkan bila tidak diubah)' : '*' }}</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password" {{ $edit ? '' : 'required' }}>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Peran *</label>
                <select name="role" class="form-select">@foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Unit kerja</label>
                <select name="unit_id" class="form-select"><option value="">—</option>
                    @foreach($units as $u)<option value="{{ $u->id }}" @selected(old('unit_id', $user->unit_id) == $u->id)>{{ $u->parent_id ? '— ' : '' }}{{ $u->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-8"><label class="form-label">Jabatan</label><input name="jabatan" class="form-control" value="{{ old('jabatan', $user->jabatan) }}"></div>
            <div class="col-12 col-md-4">
                <label class="form-label">Kode jabatan</label>
                <input name="kode_jabatan" class="form-control" value="{{ old('kode_jabatan', $user->kode_jabatan) }}">
                <div class="form-text">Dipakai penomoran Nota Dinas/Memorandum.</div>
            </div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="aktif" @checked(old('is_active', $user->is_active))><label class="form-check-label" for="aktif">Akun aktif</label></div></div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-success">Simpan</button><a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a></div>
</form>
@endsection
