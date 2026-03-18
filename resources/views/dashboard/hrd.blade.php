@extends('layouts.dashboard')

@section('title', 'Majar Signature | HRD & Karyawan')
@section('header_title', 'HRD & Karyawan')
@section('header_subtitle', 'Manajemen SDM, absensi, dan payroll tim Majar Signature')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #FF8C00;">
                <div class="small text-muted fw-bold">Total Karyawan</div>
                <h3 class="fw-bold mb-0 text-dark">{{ $employees->count() }} Orang</h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #22c55e;">
                <div class="small text-muted fw-bold">Hadir Hari Ini</div>
                <h3 class="fw-bold mb-0 text-success">12 Orang</h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #FFC107;">
                <div class="small text-muted fw-bold">Status Payroll</div>
                <h3 class="fw-bold mb-0 text-warning">Ready to Process</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Employee List -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Daftar Karyawan & Role</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-dark btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#roleManagementModal">
                            <i class="fas fa-user-tag me-1"></i> Role Management
                        </button>
                        <button class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="fas fa-plus me-1"></i> Tambah Karyawan
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Nama</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th class="text-end px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $emp)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-orange-subtle text-orange me-3 p-2 text-center fw-bold" style="width: 40px; height: 40px; background: #fff3e0; color: #FF8C00;">
                                            {{ strtoupper(substr($emp->name, 0, 1)) }}
                                        </div>
                                        <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border rounded-pill px-3">{{ ucfirst($emp->role) }}</span></td>
                                <td class="small">{{ $emp->email }}</td>
                                <td><span class="badge bg-success text-white rounded-pill px-3">Aktif</span></td>
                                <td class="text-end px-4">
                                    <button class="btn btn-light btn-sm rounded-pill border">Detail</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Panel: Payroll & Activity -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-warning py-3 text-dark">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-money-check-alt me-2"></i>Ringkasan Payroll</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="small text-muted mb-1">Periode</div>
                        <div class="fw-bold">Maret 2026</div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Gaji</span>
                        <span class="fw-bold">Rp 45.500.000</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span>Status</span>
                        <span class="badge bg-info text-dark">Draft</span>
                    </div>
                    <a href="{{ route('dashboard.hrd.payroll') }}" class="btn btn-primary w-100 rounded-3 fw-bold">KELOLA PAYROLL</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-muted"></i>Aktivitas Terbaru</h6>
                </div>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-4 py-3 border-0">
                        <div class="text-muted small">Tadi, 10:45</div>
                        <div class="text-dark fw-medium">Payroll Maret digenerate</div>
                    </div>
                    <div class="list-group-item px-4 py-3 border-0">
                        <div class="text-muted small">Kemarin, 08:12</div>
                        <div class="text-dark fw-medium">Absensi Waiter #01 Diverifikasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Role Management -->
<div class="modal fade" id="roleManagementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Role Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush">
                    @foreach(['owner', 'hrd', 'manager', 'kasir', 'waiter', 'kitchen', 'inventory'] as $role)
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <div class="fw-bold text-dark">{{ ucfirst($role) }}</div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 rounded-3 fw-bold" data-bs-dismiss="modal">SIMPAN PERUBAHAN</button>
            </div>
        </div>
    </div>
</div>
@endsection
