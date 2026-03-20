@extends('layouts.dashboard')

@section('title', 'Permintaan Restock | Majar Signature')
@section('header_title', 'Permintaan Restock')
@section('header_subtitle', 'Kelola permintaan bahan baku dari dapur atau manager')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-4">Buat Permintaan Baru</h5>
                <form action="{{ route('inventory.requests.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Bahan Baku</label>
                        <select name="ingredient_id" class="form-select rounded-3 border-2" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach($ingredients as $i)
                                <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Jumlah Dibutuhkan</label>
                        <input type="number" step="0.01" name="quantity" class="form-control rounded-3 border-2" required placeholder="0.00">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Urgency / Catatan</label>
                        <textarea name="notes" class="form-control rounded-3 border-2" rows="3" placeholder="Contoh: Stok kritis, butuh untuk weekend..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold">
                        <i class="fas fa-paper-plane me-2"></i> Kirim Permintaan
                    </button>
                </form>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0">
                    <h5 class="mb-0 fw-bold">Daftar Permintaan</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Request</th>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark small">{{ $req->user->name }}</div>
                                    <small class="text-muted">{{ $req->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $req->ingredient->name }}</div>
                                    @if($req->notes)
                                        <small class="text-muted italic">"{{ $req->notes }}"</small>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ number_format($req->quantity, 2) }} {{ $req->ingredient->unit }}</td>
                                <td>
                                    @php
                                        $statusClass = match($req->status) {
                                            'pending' => 'bg-warning-soft text-warning',
                                            'approved' => 'bg-primary-soft text-primary',
                                            'done' => 'bg-success-soft text-success',
                                            'rejected' => 'bg-danger-soft text-danger',
                                            default => 'bg-light text-muted'
                                        };
                                    @endphp
                                    <span class="badge rounded-pill px-3 py-2 {{ $statusClass }} uppercase small fw-bold">
                                        {{ $req->status }}
                                    </span>
                                    @if($req->approved_by)
                                        <div class="small text-muted mt-1" style="font-size: 0.7rem;">By: {{ $req->approvedBy->name }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($req->status === 'pending' && (auth()->user()->isManager() || auth()->user()->isOwner()))
                                        <div class="btn-group">
                                            <form action="{{ route('inventory.requests.status', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="approved">
                                                <button class="btn btn-sm btn-outline-success rounded-pill px-3 me-1">Approve</button>
                                            </form>
                                            <form action="{{ route('inventory.requests.status', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3">Reject</button>
                                            </form>
                                        </div>
                                    @elseif($req->status === 'approved' && auth()->user()->isInventory())
                                        <form action="{{ route('inventory.requests.status', $req->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="done">
                                            <button class="btn btn-sm btn-success rounded-pill px-3">Mark Done</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada permintaan restock.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(34, 197, 94, 0.1); }
    .bg-warning-soft { background-color: rgba(251, 191, 36, 0.1); }
    .bg-primary-soft { background-color: rgba(59, 130, 246, 0.1); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
    .btn-brand { background: var(--brand-gradient); color: #000; border: none; }
    .btn-brand:hover { opacity: 0.9; color: #000; }
</style>
@endsection
