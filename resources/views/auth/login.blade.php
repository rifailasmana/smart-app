<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Majar Signature</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C00;
            --primary-dark: #E67E00;
            --secondary: #FFC107;
            --dark: #1a1a1a;
            --accent-orange: #FF8C00;
            --accent-yellow: #FFC107;
        }
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #fff;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            background: #ffffff;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, var(--accent-orange), var(--accent-yellow));
            color: #000;
            border: none;
            padding: 40px 30px;
            text-align: center;
        }
        .card-header h2 {
            font-weight: 800;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .card-header p {
            margin-top: 10px;
            font-weight: 500;
            opacity: 0.8;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--accent-orange), var(--accent-yellow));
            border: none;
            color: #000;
            font-weight: 700;
            padding: 14px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(255, 140, 0, 0.4);
            filter: brightness(1.1);
        }
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #f0f0f0;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 4px rgba(255, 140, 0, 0.1);
        }
        .demo-accounts {
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            margin-top: 25px;
            border: 1px dashed #cbd5e1;
        }
        .demo-accounts h6 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .demo-accounts h6 i {
            color: var(--accent-orange);
            margin-right: 10px;
        }
        .demo-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #64748b;
        }
        .demo-user {
            font-weight: 600;
            color: var(--dark);
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">MAJAR SIGNATURE</h2>
                <p class="mb-0">Operating System Login</p>
            </div>
            <div class="card-body p-4 p-lg-5">
                @if($errors->any())
                    <div class="alert alert-danger border-0 rounded-4 mb-4">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success border-0 rounded-4 mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control bg-light border-0 rounded-end-3" value="{{ old('username') }}" placeholder="Enter username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control bg-light border-0 rounded-end-3" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-login mt-2">
                        Sign In <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>

                <div class="demo-accounts">
                    <h6><i class="fas fa-info-circle"></i> Test Accounts (Pass: 123456)</h6>
                    <div class="row">
                        <div class="col-4">
                            <div class="demo-item">Owner: <span class="demo-user">owner</span></div>
                            <div class="demo-item">Manager: <span class="demo-user">manager</span></div>
                            <div class="demo-item">HRD: <span class="demo-user">hrd</span></div>
                            <div class="demo-item">Inv: <span class="demo-user">inventory</span></div>
                        </div>
                        <div class="col-4">
                            <div class="demo-item">Cashier 1: <span class="demo-user">cashier</span></div>
                            <div class="demo-item">Cashier 2: <span class="demo-user">cashier2</span></div>
                            <div class="demo-item">Waiter 1: <span class="demo-user">waiter</span></div>
                            <div class="demo-item">Waiter 2: <span class="demo-user">waiter2</span></div>
                        </div>
                        <div class="col-4">
                            <div class="demo-item">Kitchen 1: <span class="demo-user">kitchen</span></div>
                            <div class="demo-item">Kitchen 2: <span class="demo-user">kitchen2</span></div>
                            <div class="demo-item">Admin: <span class="demo-user">admin</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
