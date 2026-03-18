<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Majar Signature - Restaurant Operating System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C00;
            --secondary: #FFC107;
            --dark: #1a1a1a;
            --light: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #000;
            padding: 120px 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .btn-hero {
            padding: 15px 50px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: white;
        }

        .feature-card {
            text-align: center;
            padding: 40px 30px;
            margin-bottom: 30px;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: var(--light);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            background: white;
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--dark);
            font-weight: 700;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Pricing Section */
        .pricing {
            padding: 100px 0;
            background: linear-gradient(180deg, #f9f9f9 0%, white 100%);
        }

        .pricing-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
        }

        .pricing-card.featured {
            transform: scale(1.05);
            border-color: var(--primary);
            box-shadow: 0 20px 50px rgba(255, 107, 107, 0.3);
        }

        .pricing-card:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            border-color: var(--secondary);
        }

        .badge-featured {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .pricing-tier {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .pricing-price {
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 800;
            margin: 20px 0;
        }

        .pricing-period {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
            text-align: left;
        }

        .pricing-features li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
            color: #666;
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features i {
            color: var(--secondary);
            margin-right: 10px;
            font-weight: bold;
        }

        .pricing-features .disabled {
            color: #ccc;
        }

        .pricing-features .disabled i {
            color: #ddd;
        }

        .btn-pricing {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        /* Stats Section */
        .stats {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta {
            padding: 80px 0;
            background: var(--dark);
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 30px;
            font-weight: 800;
        }

        /* Footer */
        footer {
            background: #1a252f;
            color: #999;
            padding: 30px 0;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .pricing-card.featured {
                transform: scale(1);
            }
        }

        .subscription-period {
            font-size: 0.85rem;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#" style="font-size: 1.5rem; color: var(--primary);">
                <i class="fas fa-utensils"></i> Majar Signature
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Harga</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Hubungi Kami</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-sm ms-2" href="{{ route('login') }}" style="background: var(--primary); color: white;">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Sistem Pemesan Digital untuk Restoran Anda</h1>
            <p>Tingkatkan efisiensi operasional dengan solusi pemesanan yang cepat, mudah, dan terintegrasi</p>
            <button class="btn btn-light btn-hero me-3" onclick="document.getElementById('pricing').scrollIntoView({behavior: 'smooth'})">
                <i class="fas fa-check"></i> Lihat Paket Harga
            </button>
            <a href="{{ route('login') }}" class="btn btn-warning btn-hero">
                <i class="fas fa-arrow-right"></i> Login
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="text-center mb-5" style="font-size: 2.5rem; font-weight: 800;">
                Mengapa Memilih Majar Signature OS?
            </h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Menu Digital</h3>
                        <p>Tampilkan menu makanan & minuman dengan foto menarik. Mudah diubah kapan saja tanpa biaya tambahan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h3>Kitchen Display System</h3>
                        <p>Pesanan langsung tampil ke dapur dengan status real-time. Percepat proses memasak hingga 40%.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3>Laporan & Analitik</h3>
                        <p>Pantau pendapatan, menu terlaris, dan performa kasir dalam satu dashboard yang mudah dipahami.</p>
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>Aman & Terpercaya</h3>
                        <p>Data customer dan order dienkripsi. Backup otomatis setiap hari untuk keamanan maksimal.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Multi User</h3>
                        <p>Kelola akses kasir, pelayan, dan kitchen staff dengan role berbeda sesuai kebutuhan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Support 24/7</h3>
                        <p>Tim support siap membantu Anda kapan saja. Maintenance gratis untuk paket premium.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="container">
            <h2 class="text-center mb-5" style="font-size: 2.5rem; font-weight: 800;">
                Pilih Paket yang Sesuai
            </h2>
            <div class="row g-4">
                <!-- Starter Plan -->
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="pricing-tier">Paket Starter</div>
                        <div class="pricing-price">Rp 150.000</div>
                        <div class="subscription-period">1 bulan pertama</div>
                        <div class="pricing-period">Dilanjutkan Rp 250.000/bulan</div>
                        
                        <ul class="pricing-features">
                            <li><i class="fas fa-check-circle"></i> Menu Digital Unlimited</li>
                            <li><i class="fas fa-check-circle"></i> Kitchen Display System</li>
                            <li><i class="fas fa-check-circle"></i> Multi User (5 akun staff)</li>
                            <li><i class="fas fa-check-circle"></i> Dashboard Penjualan Dasar</li>
                            <li><i class="fas fa-times-circle disabled"></i> Best Sale Menu Report</li>
                            <li><i class="fas fa-times-circle disabled"></i> Dashboard Lengkap</li>
                            <li><i class="fas fa-times-circle disabled"></i> Maintenance Gratis</li>
                        </ul>
                        
                        <button class="btn btn-outline-success btn-pricing" data-bs-toggle="modal" data-bs-target="#contactModal">
                            <i class="fas fa-phone"></i> Hubungi Sales
                        </button>
                    </div>
                </div>

                <!-- Professional Plan -->
                <div class="col-md-4">
                    <div class="pricing-card featured">
                        <div class="badge-featured">PALING POPULER</div>
                        <div class="pricing-tier">Paket Professional</div>
                        <div class="pricing-price">Rp 250.000</div>
                        <div class="subscription-period">1 bulan pertama</div>
                        <div class="pricing-period">Dilanjutkan Rp 350.000/bulan</div>
                        
                        <ul class="pricing-features">
                            <li><i class="fas fa-check-circle"></i> Menu Digital Unlimited</li>
                            <li><i class="fas fa-check-circle"></i> Kitchen Display System</li>
                            <li><i class="fas fa-check-circle"></i> Multi User (15 akun staff)</li>
                            <li><i class="fas fa-check-circle"></i> Dashboard Lengkap</li>
                            <li><i class="fas fa-check-circle"></i> Best Sale Menu Report</li>
                            <li><i class="fas fa-check-circle"></i> Laporan Perbulan Detail</li>
                            <li><i class="fas fa-check-circle"></i> Maintenance 6 Bulan GRATIS</li>
                        </ul>
                        
                        <button class="btn btn-outline-success btn-pricing" data-bs-toggle="modal" data-bs-target="#contactModal">
                            <i class="fas fa-phone"></i> Hubungi Sales
                        </button>
                    </div>
                </div>

                <!-- Enterprise Plan -->
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="pricing-tier">Paket Enterprise</div>
                        <div class="pricing-price">Custom</div>
                        <div class="subscription-period">Hubungi kami untuk harga</div>
                        <div class="pricing-period" style="visibility: hidden;">-</div>
                        
                        <ul class="pricing-features">
                            <li><i class="fas fa-check-circle"></i> Semua Fitur Professional</li>
                            <li><i class="fas fa-check-circle"></i> Hardware Kasir (PC/Tablet)</li>
                            <li><i class="fas fa-check-circle"></i> Kitchen Display (Monitor LED)</li>
                            <li><i class="fas fa-check-circle"></i> Customer Display (TV)</li>
                            <li><i class="fas fa-check-circle"></i> Thermal Printer</li>
                            <li><i class="fas fa-check-circle"></i> Training Staff Onsite</li>
                            <li><i class="fas fa-check-circle"></i> Support Premium Unlimited</li>
                        </ul>
                        
                        <button class="btn btn-outline-success btn-pricing" data-bs-toggle="modal" data-bs-target="#contactModal">
                            <i class="fas fa-phone"></i> Hubungi Sales
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="stat-item mb-4">
                        <h3>500+</h3>
                        <p>Restoran Terdaftar</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item mb-4">
                        <h3>50K+</h3>
                        <p>Pesanan Harian</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item mb-4">
                        <h3>98%</h3>
                        <p>Customer Satisfaction</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Siap Meningkatkan Bisnis Restoran Anda?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; opacity: 0.9;">Hubungi admin untuk membuat akun restoran baru Anda</p>
            <a href="{{ route('login') }}" class="btn btn-warning btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login Sekarang
            </a>
        </div>
    </section>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Nama Restoran</label>
                            <input type="text" class="form-control" placeholder="Nama restoran Anda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" placeholder="email@restoran.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-control" placeholder="08xx-xxxx-xxxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pesan</label>
                            <textarea class="form-control" rows="4" placeholder="Ceritakan kebutuhan Anda..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary">Kirim Pesan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 Majar Signature. | <a href="#" style="color: #999; text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: #999; text-decoration: none;">Terms of Service</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
