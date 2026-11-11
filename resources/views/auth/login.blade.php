<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — E-Office Dinkes Kampar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#0f3d2e,#1f7a5a);min-height:100vh" class="d-flex align-items-center">
<div class="container" style="max-width:420px">
    <div class="card shadow border-0">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="bi bi-envelope-paper-heart text-success" style="font-size:2.5rem"></i>
                <h1 class="h4 mt-2 mb-0">E-Office</h1>
                <div class="text-muted">Dinas Kesehatan Kabupaten Kampar</div>
            </div>

            <form method="post" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Kata sandi</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
                <button class="btn btn-success w-100">Masuk</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
