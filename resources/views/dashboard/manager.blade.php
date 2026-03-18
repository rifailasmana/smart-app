@extends('layouts.dashboard')

@section('title', 'Majar Signature | Manager Dashboard')
@section('header_title', 'Majar Signature | Manager Dashboard')
@section('header_subtitle', 'Monitoring operasional, menu, dan approval')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4 mb-4">
        <!-- Monitoring Stats -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: linear-gradient(135deg, #FF8C00, #FFC107); color: #000;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-white rounded-3 p-3 me-3">
                        <i class="fas fa-shopping-cart fa-lg text-dark"></i>
                    </div>
                    <div>
                        <div class="small fw-bold opacity-75">Pesanan Hari Ini</div>
                        <h3 class="fw-bold mb-0">{{ $orders->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #FF8C00;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-light rounded-3 p-3 me-3">
                        <i class="fas fa-money-bill-wave fa-lg text-orange" style="color: #FF8C00;"></i>
                    </div>
                    <div>
                        <div class="small text-muted fw-bold">Total Penjualan</div>
                        <h3 class="fw-bold mb-0">Rp {{ number_format($orders->where('status', 'paid')->sum('total'), 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #FFC107;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-light rounded-3 p-3 me-3">
                        <i class="fas fa-utensils fa-lg text-yellow" style="color: #FFC107;"></i>
                    </div>
                    <div>
                        <div class="small text-muted fw-bold">Menu Aktif</div>
                        <h3 class="fw-bold mb-0">{{ \App\Models\MenuItem::where('active', true)->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Order Monitoring -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Monitoring Pesanan & Approval Void</h5>
                    <div class="badge bg-warning text-dark px-3 rounded-pill">Real-time</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Kode Order</th>
                                <th>Meja</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th class="text-end px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td class="px-4 fw-bold text-dark">#{{ $order->code }}</td>
                                <td>{{ $order->table ? $order->table->name : 'Takeaway' }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($order->status) {
                                            'paid' => 'bg-success text-white',
                                            'pending' => 'bg-warning text-dark',
                                            'cancelled' => 'bg-danger text-white',
                                            default => 'bg-info text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill px-3">{{ strtoupper($order->status) }}</span>
                                </td>
                                <td class="fw-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                <td class="text-end px-4">
                                    @if($order->status != 'cancelled')
                                    <form action="{{ route('manager.void', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 fw-bold" onclick="return confirm('Apakah Anda yakin ingin melakukan VOID pada pesanan ini?')">VOID</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Panel: Coupons & Menu Quick Toggle -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-ticket-alt me-2 text-warning"></i>Buat Kupon Diskon</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('manager.coupon.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kode Kupon</label>
                            <input type="text" name="code" class="form-control rounded-3" placeholder="Contoh: MAJAR20" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Diskon (%)</label>
                            <input type="number" name="discount_percent" class="form-control rounded-3" min="1" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kategori Target</label>
                            <select name="valid_for_category" class="form-select rounded-3">
                                <option value="Regular">Regular</option>
                                <option value="Reservation">Reservation</option>
                                <option value="Majar Priority">Majar Priority</option>
                                <option value="Majar Signature">Majar Signature</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold">SIMPAN KUPON</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">Persetujuan Restock</h6>
                    <span class="badge bg-danger rounded-pill">3 Perlu Review</span>
                </div>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-dark">Daging Ayam (Gudang)</div>
                            <div class="text-muted">Request: 50kg</div>
                        </div>
                        <button class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                    </div>
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-dark">Minyak Goreng</div>
                            <div class="text-muted">Request: 10 Liter</div>
                        </div>
                        <button class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
