@extends('layouts.dashboard')

@section('title', 'Admin Dashboard - Majar Signature')
@section('header_title', 'Admin Dashboard')
@section('header_subtitle', 'Kelola seluruh restoran dan pengguna')

@section('content')
    <style>
        .admin-main {
            margin-top: 1rem;
            padding: 1.5rem;
        }
        .admin-restaurant-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #eee;
            border-left: 5px solid var(--brand-orange);
        }
        .admin-restaurant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
        }
        .admin-restaurant-card h5 {
            margin-bottom: 10px;
        }
        .admin-alert-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 999px;
        }
        .admin-card-light {
            border-radius: 0.85rem;
            border: none;
        }
    </style>

    <div class="admin-main container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-store"></i> Daftar Restoran</h2>
            <button class="btn btn-primary" onclick="showCreateWarungModal()">
                <i class="fas fa-plus"></i> Tambah Restoran
            </button>
        </div>

        <div class="row">
            @forelse($warungs as $warung)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="admin-restaurant-card" onclick="viewRestaurant({{ $warung->id }})">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 style="margin: 0;">{{ $warung->name }}</h5>
                            @if(count($warung->alerts ?? []) > 0)
                                <span class="badge bg-{{ $warung->alerts[0]['type'] == 'warning' ? 'warning' : 'info' }}" title="{{ collect($warung->alerts)->pluck('message')->join(', ') }}">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                            @endif
                        </div>
                        <div class="mb-2">
                            @php
                                $tierColors = [
                                    'starter' => 'secondary',
                                    'professional' => 'primary',
                                    'enterprise' => 'dark'
                                ];
                                $tier = $warung->subscription_tier ?? 'starter';
                            @endphp
                            <span class="badge bg-{{ $tierColors[$tier] ?? 'secondary' }} text-uppercase" style="font-size: 0.7rem;">
                                {{ $tier }}
                            </span>
                        </div>
                        <p class="text-muted mb-2">
                            <i class="fas fa-link"></i> {{ $warung->slug }}.{{ env('SMARTORDER_DOMAIN', 'smartorder.local') }}
                        </p>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="card bg-light p-2 admin-card-light">
                                    <small class="text-muted d-block">Penjualan Minggu Ini</small>
                                    <strong class="text-success">Rp {{ number_format($warung->weekly_revenue ?? 0, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light p-2 admin-card-light">
                                    <small class="text-muted d-block">Jumlah Staff</small>
                                    <strong>{{ $warung->staff_count ?? 0 }} orang</strong>
                                </div>
                            </div>
                        </div>

                        @if(count($warung->alerts ?? []) > 0)
                            <div class="mb-2">
                                @foreach($warung->alerts as $alert)
                                    <div class="alert alert-{{ $alert['type'] }} admin-alert-sm py-1 px-2 mb-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-{{ $alert['type'] == 'warning' ? 'exclamation-triangle' : 'info-circle' }}"></i> {{ $alert['message'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mb-2">
                            <strong>Link Customer (QRIS):</strong><br>
                            <a href="{{ $warung->customer_url ?? '#' }}" target="_blank" onclick="event.stopPropagation();" class="text-primary text-break" style="font-size: 0.85rem;">
                                <i class="fas fa-qrcode"></i> {{ $warung->customer_url ?? 'N/A' }}
                            </a>
                        </div>
                        <small class="text-muted">Code: {{ $warung->code }}</small>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-store-slash fa-2x mb-2"></i>
                        <p>Belum ada restoran. Tambah restoran pertama!</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Create Restaurant Modal -->
    <div id="createWarungModal" class="modal-backdrop">
        <div class="modal-panel">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Create New Restaurant</h5>
                <button type="button" class="btn-close" onclick="hideCreateWarungModal()"></button>
            </div>
            <form id="createWarungForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Restaurant Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unique Code *</label>
                        <input type="text" class="form-control" name="code" pattern="[A-Z0-9]{3,10}" required>
                        <small class="text-muted">3-10 characters, uppercase letters and numbers only</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Subscription Tier *</label>
                            <select class="form-select" name="subscription_tier" required>
                                <option value="starter">Starter (Rp 150K/month)</option>
                                <option value="professional">Professional (Rp 250K/month)</option>
                                <option value="enterprise">Enterprise (Custom)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monthly Price *</label>
                            <input type="number" class="form-control" name="monthly_price" step="10000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideCreateWarungModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Restaurant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewRestaurant(id) {
            window.location.href = '/admin/restaurants/' + id;
        }

        function showCreateWarungModal() {
            var modal = document.getElementById('createWarungModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function hideCreateWarungModal() {
            var modal = document.getElementById('createWarungModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        document.getElementById('createWarungForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
            submitBtn.disabled = true;
            
            fetch('/admin/restaurants', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    hideCreateWarungModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create restaurant');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
@endsection
