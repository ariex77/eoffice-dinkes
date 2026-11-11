<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') — E-Office Dinkes Kampar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        #sidebar { --bs-offcanvas-width: 260px; background: #0f3d2e; }
        @media (min-width: 992px) {
            #sidebar { flex: 0 0 260px; width: 260px; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        }
        #sidebar .nav-link { color: #cfe5db; border-radius: .5rem; padding: .55rem .9rem; }
        #sidebar .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        #sidebar .nav-link.active { background: #1f7a5a; color: #fff; }
        .brand small { color: #9cc5b4; }
        .table td, .table th { vertical-align: middle; }
        .label-sm { font-size: .8rem; color: #6c757d; }
    </style>
</head>
<body class="bg-body-tertiary">
@php
    $me = auth()->user();
    $nDisp = $me->disposisiMasuk()->where('status', 'baru')->count();
    $nav = fn (string $pattern) => request()->routeIs($pattern) ? 'active' : '';
@endphp

<nav class="navbar bg-white border-bottom d-lg-none sticky-top">
    <div class="container-fluid">
        <button class="btn btn-outline-success" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="fw-semibold">E-Office Dinkes Kampar</span>
        <a href="{{ route('disposisi.index') }}" class="btn btn-outline-secondary position-relative">
            <i class="bi bi-bell"></i>
            @if($nDisp)<span class="badge text-bg-danger position-absolute top-0 start-100 translate-middle">{{ $nDisp }}</span>@endif
        </a>
    </div>
</nav>

<div class="d-flex">
    <aside id="sidebar" class="offcanvas-lg offcanvas-start text-white" tabindex="-1">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold">Menu</span>
            <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <div class="brand mb-4 d-none d-lg-block">
                <div class="fw-bold fs-5"><i class="bi bi-envelope-paper-heart me-1"></i> E-Office</div>
                <small>Dinas Kesehatan Kab. Kampar</small>
            </div>

            <ul class="nav nav-pills flex-column gap-1 mb-auto">
                <li><a class="nav-link {{ $nav('dashboard') }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Beranda</a></li>
                <li><a class="nav-link {{ $nav('surat-masuk.*') }}" href="{{ route('surat-masuk.index') }}"><i class="bi bi-inbox me-2"></i>Surat Masuk</a></li>
                <li><a class="nav-link {{ $nav('surat-keluar.*') }}" href="{{ route('surat-keluar.index') }}"><i class="bi bi-send me-2"></i>Surat Keluar</a></li>
                <li><a class="nav-link {{ $nav('penomoran.*') }}" href="{{ route('penomoran.index') }}"><i class="bi bi-123 me-2"></i>Penomoran</a></li>
                <li>
                    <a class="nav-link d-flex justify-content-between {{ $nav('disposisi.*') }}" href="{{ route('disposisi.index') }}">
                        <span><i class="bi bi-diagram-3 me-2"></i>Disposisi</span>
                        @if($nDisp)<span class="badge text-bg-danger">{{ $nDisp }}</span>@endif
                    </a>
                </li>
                @if($me->hasRole('admin'))
                    <li class="mt-3 px-2 text-uppercase small" style="color:#7fb09a">Administrasi</li>
                    <li><a class="nav-link {{ $nav('users.*') }}" href="{{ route('users.index') }}"><i class="bi bi-people me-2"></i>Pengguna</a></li>
                @endif
            </ul>

            <div class="border-top border-secondary pt-3 mt-3 small">
                <div class="fw-semibold">{{ $me->name }}</div>
                <div class="text-white-50 mb-2">{{ $me->roleLabel() }}@if($me->unit) · {{ $me->unit->kode_nomor ?? $me->unit->nama }}@endif</div>
                <form method="post" action="{{ route('logout') }}">@csrf
                    <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="flex-grow-1 p-3 p-lg-4" style="min-width:0">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Periksa kembali isian Anda:</div>
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
