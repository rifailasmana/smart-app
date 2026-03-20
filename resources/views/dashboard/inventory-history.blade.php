@extends('layouts.dashboard')

@section('title', 'Riwayat Mutasi Stok | Majar Signature')
@section('header_title', 'Riwayat Mutasi Stok')
@section('header_subtitle', 'Log lengkap pergerakan barang masuk, keluar, dan penyesuaian')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Audit Log Inventaris</h5>
            <div class="d-flex gap-2">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="history">
                    <select name="type" class="form-select rounded-pill px-3 small border-0 bg-light" onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="incoming" {{ request('type') == 'incoming' ? 'selected' : '' }}>Masuk</option>
                        <option value="usage" {{ request('type') == 'usage' ? 'selected' : '' }}>Pemakaian</option>
                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
                        <option value="waste" {{ request('type') == 'waste' ? 'selected' : '' }}>Rusak/Hilang</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Tanggal & Waktu</th>
                        <th>Bahan Baku</th>
                        <th>Tipe</th>
                        <th class="text-center">Jumlah</th>
                        <th>Supplier / Ref</th>
                        <th>User</th>
                        <th class="pe-4">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">{{ $log->created_at->format('d M Y') }}</div>
                            <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                        </td>
                        <td class="fw-bold text-dark">{{ $log->ingredient->name }}</td>
                        <td>
                            <span class="badge rounded-pill px-3 py-2 {{ 
                                $log->type === 'incoming' ? 'bg-success-soft text-success' : 
                                ($log->type === 'usage' ? 'bg-primary-soft text-primary' : 
                                ($log->type === 'waste' ? 'bg-danger-soft text-danger' : 'bg-warning-soft text-warning')) 
                            }} uppercase small fw-bold">
                                {{ strtoupper($log->type) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <h6 class="mb-0 fw-black {{ in_array($log->type, ['usage', 'waste']) ? 'text-danger' : 'text-success' }}">
                                {{ in_array($log->type, ['usage', 'waste']) ? '-' : '+' }}{{ number_format($log->quantity, 2) }}
                                <small class="text-muted fw-normal">{{ $log->ingredient->unit }}</small>
                            </h6>
                        </td>
                        <td>
                            @if($log->supplier)
                                <span class="text-dark small fw-bold"><i class="fas fa-truck me-1"></i> {{ $log->supplier->name }}</span>
                            @elseif($log->reference_type === 'order')
                                <span class="text-primary small fw-bold">Order #{{ $log->reference_id }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="small text-dark">{{ $log->user->name }}</td>
                        <td class="pe-4 small text-muted italic">{{ $log->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada riwayat pergerakan barang.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $logs->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(34, 197, 94, 0.1); }
    .bg-warning-soft { background-color: rgba(251, 191, 36, 0.1); }
    .bg-primary-soft { background-color: rgba(59, 130, 246, 0.1); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
    .fw-black { font-weight: 900; }
</style>
@endsection
