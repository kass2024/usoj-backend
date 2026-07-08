<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Document Upload Portal — Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f5; font-family: Arial, Helvetica, sans-serif; }
        .portal-card { max-width: 420px; margin: 8vh auto; }
        .brand { color: #007a33; font-weight: 700; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow portal-card">
        <div class="card-body p-4">
            <h4 class="brand mb-1">University of Saint Joseph Mbarara</h4>
            <p class="text-muted mb-3">Private document upload portal</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="post" action="{{ route('document-portal.login.submit', $link->slug) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-success w-100">Log in</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
