@extends('layouts.app')

@section('title', 'Penjadwalan Shift Staff')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1">Jadwal Shift Staff</h4>
                        <p class="text-muted mb-0">Kelola jadwal kerja karyawan untuk setiap harinya.</p>
                    </div>
                    <form action="{{ route('hrd.shifts') }}" method="GET" class="d-flex gap-2">
                        <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Tambah Shift -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Tambah Jadwal Shift</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('hrd.shifts.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Karyawan</label>
                            <select name="user_id" class="form-select select2" required>
                                <option value="">-- Pilih Karyawan --</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->role }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Peran Shift</label>
                            <select name="role" class="form-select" required>
                                <option value="waiter">Waiter</option>
                                <option value="kasir">Kasir</option>
                                <option value="kitchen">Kitchen</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-calendar-plus"></i> Simpan Jadwal
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Shift -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Daftar Shift: {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Karyawan</th>
                                    <th class="text-center">Peran</th>
                                    <th class="text-center">Waktu</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shifts as $shift)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $shift->user->name }}</div>
                                            <div class="small text-muted">{{ $shift->user->username }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border rounded-pill px-3">{{ strtoupper($shift->role) }}</span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            {{ \Carbon\Carbon::parse($shift->started_at)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($shift->ended_at)->format('H:i') }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('hrd.shifts.destroy', $shift->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus jadwal shift ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x display-4 mb-3"></i>
                                            <p>Belum ada jadwal shift untuk tanggal ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
