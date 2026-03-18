<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Majar Signature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF8C00;
            --secondary: #FFC107;
            --accent: #E67E00;
            --dark: #111827;
            --muted: #6b7280;
        }
        body {
            background: radial-gradient(circle at top left, #fff7e6 0%, #FFE8B3 35%, #FF8C00 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .register-container {
            max-width: 520px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 18px 18px 0 0;
            padding: 28px 30px;
            text-align: center;
        }
        .btn-register {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 12px;
            border-radius: 999px;
        }
        .btn-register:hover {
            opacity: 0.95;
            color: #000;
        }
        .form-label {
            font-weight: 600;
            color: var(--dark);
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 140, 0, 0.18);
        }
        .has-error .form-control {
            border-color: #dc3545;
        }
        .error-text {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
        }
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-store"></i> Daftar Restoran Baru</h3>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Nama Restoran</label>
                        <input type="text" class="form-control @error('warung_name') is-invalid @enderror" name="warung_name" value="{{ old('warung_name') }}" required>
                        @error('warung_name')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kode Restoran (mis: BALI)</label>
                        <input type="text" class="form-control @error('warung_code') is-invalid @enderror" name="warung_code" value="{{ old('warung_code') }}" maxlength="10" required>
                        <small class="text-muted">Kode unik untuk identifikasi restoran</small>
                        @error('warung_code')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Owner/Pemilik</label>
                        <input type="text" class="form-control @error('owner_name') is-invalid @enderror" name="owner_name" value="{{ old('owner_name') }}" required>
                        @error('owner_name')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        <small class="text-muted">Minimal 8 karakter</small>
                        @error('password')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Paket</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="subscription_tier" id="starter" value="starter" {{ old('subscription_tier') === 'starter' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="starter">
                                <strong>Starter</strong> - Rp 150.000/bulan (dilanjut 250k)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="subscription_tier" id="professional" value="professional" {{ old('subscription_tier') === 'professional' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="professional">
                                <strong>Professional</strong> - Rp 250.000/bulan (dilanjut 350k) ⭐
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="subscription_tier" id="enterprise" value="enterprise" {{ old('subscription_tier') === 'enterprise' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="enterprise">
                                <strong>Enterprise</strong> - Custom (hubungi sales)
                            </label>
                        </div>
                        @error('subscription_tier')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-register w-100 mb-3">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="login-link">
                    Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
