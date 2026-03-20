@extends('layouts.dashboard')

@section('title', 'Majar Signature | HRD Dashboard')
@section('header_title', 'Human Resource Operating System')
@section('header_subtitle', 'Manajemen karyawan, absensi, dan payroll')

@section('content')
<div class="container-fluid py-4">
    @if($tab === 'dashboard')
        <!-- 🏠 1. DASHBOARD HRD -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-brand">
                    <div class="small fw-bold text-dark opacity-75 uppercase">Total Karyawan Aktif</div>
                    <h2 class="fw-black mb-0">{{ $totalEmployees }} <small class="fs-6 text-dark opacity-50">Orang</small></h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="small text-muted fw-bold uppercase">Masuk Hari Ini</div>
                    <h2 class="fw-black mb-0 text-success">{{ $presentToday }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-danger-soft">
                    <div class="small text-danger fw-bold uppercase">Karyawan Telat</div>
                    <h2 class="fw-black mb-0 text-danger">{{ $lateToday }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-warning-soft">
                    <div class="small text-warning fw-bold uppercase">Cuti / Izin</div>
                    <h2 class="fw-black mb-0 text-warning">{{ $onLeaveToday }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-start border-primary border-4">
                    <div class="small text-primary fw-bold uppercase">Reminder Payroll</div>
                    <div class="fw-bold text-dark mt-1">Periode: {{ now()->format('F Y') }}</div>
                    <div class="small text-muted">Batas generate: Tgl 25</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2 text-brand"></i> Kehadiran Real-time</h6>
                        <a href="?tab=attendance" class="small text-brand fw-bold text-decoration-none">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nama</th>
                                    <th>Status</th>
                                    <th>Clock In</th>
                                    <th class="text-end pe-4">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances->take(5) as $att)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $att->user->name }}</td>
                                    <td>
                                        <span class="badge rounded-pill px-3 {{ 
                                            $att->status === 'present' ? 'bg-success-soft text-success' : 
                                            ($att->status === 'late' ? 'bg-danger-soft text-danger' : 'bg-warning-soft text-warning') 
                                        }} uppercase small">
                                            {{ $att->status }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $att->clock_in ? date('H:i', strtotime($att->clock_in)) : '-' }}</td>
                                    <td class="text-end pe-4 small text-muted italic">{{ $att->notes ?: '-' }}</td>
                                </tr>
                                @endforeach
                                @if($attendances->isEmpty())
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada data absensi hari ini</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h6 class="fw-bold mb-4">Aktivitas Terbaru</h6>
                    <div class="list-group list-group-flush small">
                        @if($expiringCerts->count() > 0)
                            @foreach($expiringCerts as $cert)
                            <div class="list-group-item px-0 py-3 border-0 bg-danger-soft rounded-3 mb-2 px-2">
                                <div class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Health Cert Expiring</div>
                                <div class="text-dark small">{{ $cert->user->name }} ({{ date('d M', strtotime($cert->health_certificate_expiry)) }})</div>
                            </div>
                            @endforeach
                        @else
                            <div class="list-group-item px-0 py-3 border-0">
                                <div class="text-muted small">Tadi, 10:45</div>
                                <div class="text-dark fw-medium">Payroll Maret digenerate</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Inventaris Seragam Quick View -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold mb-3">Monitoring Inventaris</h6>
                    <div class="small text-muted mb-3">Karyawan dengan seragam belum lengkap:</div>
                    @foreach($employees->whereNull('employeeDetail.uniform_details')->take(3) as $emp)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">{{ $emp->name }}</span>
                            <span class="badge bg-danger-soft text-danger x-small">Belum Data</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif($tab === 'employees')
        <!-- 👥 2. DATA KARYAWAN -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Database Karyawan</h5>
                <button class="btn btn-brand px-4 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-user-plus me-2"></i> Tambah Karyawan
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama / NIK</th>
                            <th>Role</th>
                            <th>Kontak / Email</th>
                            <th>Join Date</th>
                            <th class="text-center">Gaji Pokok</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                <small class="text-muted">NIK: {{ $emp->employeeDetail?->nik ?? '-' }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper($emp->role) }}</span></td>
                            <td>
                                <div class="small fw-medium">{{ $emp->email }}</div>
                                <div class="small text-muted">{{ $emp->whatsapp ?: '-' }}</div>
                                @if($emp->employeeDetail?->health_certificate_expiry)
                                    @php 
                                        $expiry = \Carbon\Carbon::parse($emp->employeeDetail->health_certificate_expiry);
                                        $isExpired = $expiry->isPast();
                                        $isNear = !$isExpired && $expiry->diffInDays(now()) < 30;
                                    @endphp
                                    <div class="mt-1">
                                        <span class="badge {{ $isExpired ? 'bg-danger' : ($isNear ? 'bg-warning text-dark' : 'bg-success') }} x-small" style="font-size: 0.6rem;">
                                            <i class="fas fa-heartbeat me-1"></i> Health Cert: {{ $expiry->format('d/m/y') }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="small">{{ $emp->employeeDetail?->join_date ? date('d M Y', strtotime($emp->employeeDetail->join_date)) : '-' }}</td>
                            <td class="text-center fw-bold">Rp {{ number_format($emp->employeeDetail?->base_salary ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light border" title="Edit Data"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-light border text-info" title="Lihat Profil"><i class="fas fa-eye"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($tab === 'attendance')
        <!-- 🕒 3. ABSENSI & SHIFT -->
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-dark text-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <h5 class="mb-0 fw-bold">Grid Penjadwalan {{ $viewMode === 'month' ? 'Bulanan' : 'Mingguan' }}</h5>
                                <small class="text-warning">Klik sel untuk mengubah shift</small>
                            </div>
                            
                            <!-- View Toggle & Date Picker -->
                            <form action="{{ route('dashboard.hrd') }}" method="GET" class="d-flex gap-2 align-items-center bg-white bg-opacity-10 p-2 rounded-pill">
                                <input type="hidden" name="tab" value="attendance">
                                <select name="view_mode" class="form-select form-select-sm border-0 bg-transparent text-white w-auto" onchange="this.form.submit()">
                                    <option value="week" {{ $viewMode === 'week' ? 'selected' : '' }}>Mingguan</option>
                                    <option value="month" {{ $viewMode === 'month' ? 'selected' : '' }}>Bulanan</option>
                                </select>
                                <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-transparent text-white w-auto" value="{{ $startDate->toDateString() }}" onchange="this.form.submit()">
                            </form>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-warning btn-sm px-3 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#shiftSettingsModal">
                                <i class="fas fa-cog me-1"></i> Config Jam
                            </button>
                            <button class="btn btn-brand btn-sm px-3 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                                <i class="fas fa-plus me-1"></i> Custom
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-bordered align-middle mb-0 text-center table-sm">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="ps-4 text-start" style="min-width: 180px;">Karyawan</th>
                                    @foreach($weekDates as $date)
                                    <th class="{{ $date->isToday() ? 'bg-brand text-dark' : '' }}" style="min-width: 60px;">
                                        <div style="font-size: 0.7rem;">{{ $date->format('D') }}</div>
                                        <div class="small">{{ $date->format('d/m') }}</div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $emp)
                                <tr>
                                    <td class="ps-4 text-start">
                                        <div class="fw-bold small">{{ $emp->name }}</div>
                                        <div class="text-muted" style="font-size: 0.6rem;">{{ strtoupper($emp->role) }}</div>
                                    </td>
                                    @foreach($weekDates as $date)
                                    @php 
                                        $shift = $shifts->where('user_id', $emp->id)
                                            ->filter(fn($s) => $s->started_at->toDateString() == $date->toDateString())
                                            ->first();
                                        
                                        $type = 'off';
                                        if ($shift) {
                                            $hour = $shift->started_at->format('H:i');
                                            $matchedSetting = $shiftSettings->first(fn($s) => substr($s->start_time, 0, 5) == $hour);
                                            $type = $matchedSetting ? $matchedSetting->type : 'custom';
                                        }
                                    @endphp
                                    <td class="p-0">
                                        <div class="dropdown h-100">
                                            <button class="btn btn-link w-100 h-100 p-2 text-decoration-none dropdown-toggle no-caret {{ 
                                                $type === 'pagi' ? 'bg-success-soft text-success fw-bold' : 
                                                ($type === 'sore' ? 'bg-primary-soft text-primary fw-bold' : 
                                                ($type === 'malam' ? 'bg-dark text-white fw-bold' : 
                                                ($type === 'custom' ? 'bg-warning-soft text-warning fw-bold' : 'text-muted'))) 
                                            }}" data-bs-toggle="dropdown" style="min-height: 45px; font-size: 0.8rem;">
                                                {{ $type === 'pagi' ? 'P' : ($type === 'sore' ? 'S' : ($type === 'malam' ? 'M' : ($type === 'custom' ? 'C' : '-'))) }}
                                            </button>
                                            <ul class="dropdown-menu shadow border-0 rounded-3">
                                                @foreach($shiftSettings as $setting)
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('hrd.shift.quick', ['user_id' => $emp->id, 'date' => $date->toDateString(), 'shift_type' => $setting->type]) }}">
                                                        <span class="badge {{ $setting->type == 'pagi' ? 'bg-success' : ($setting->type == 'sore' ? 'bg-primary' : 'bg-dark') }} me-2">
                                                            {{ strtoupper(substr($setting->type, 0, 1)) }}
                                                        </span> 
                                                        {{ ucfirst($setting->type) }} ({{ substr($setting->start_time, 0, 5) }} - {{ substr($setting->end_time, 0, 5) }})
                                                    </a>
                                                </li>
                                                @endforeach
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item py-2 text-danger" href="{{ route('hrd.shift.quick', ['user_id' => $emp->id, 'date' => $date->toDateString(), 'shift_type' => 'off']) }}"><i class="fas fa-times me-2"></i> Libur (OFF)</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Live Monitoring Absensi</h5>
                        <form action="{{ route('dashboard.hrd') }}" method="GET" class="d-flex gap-2">
                            <input type="hidden" name="tab" value="attendance">
                            <input type="date" name="date" class="form-control w-auto rounded-pill" value="{{ $selectedDate }}" onchange="this.form.submit()">
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nama</th>
                                    <th>Status</th>
                                    <th>Clock In/Out</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $emp)
                                @php $att = $attendances->where('user_id', $emp->id)->first(); @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $emp->name }}</div>
                                        <small class="text-muted">{{ strtoupper($emp->role) }}</small>
                                    </td>
                                    <td>
                                        @if($att)
                                            <span class="badge rounded-pill px-3 {{ 
                                                $att->status === 'present' ? 'bg-success text-white' : 
                                                ($att->status === 'late' ? 'bg-danger text-white' : 'bg-warning text-dark') 
                                            }} uppercase small">{{ $att->status }}</span>
                                        @else
                                            <span class="badge bg-light text-muted border rounded-pill px-3">BELUM ABSEN</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        {{ $att && $att->clock_in ? date('H:i', strtotime($att->clock_in)) : '--:--' }} - 
                                        {{ $att && $att->clock_out ? date('H:i', strtotime($att->clock_out)) : '--:--' }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#editAttendanceModal{{ $emp->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <!-- Leave Tracking -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-4 px-4 border-0">
                        <h5 class="mb-0 fw-bold">Pengajuan Izin / Cuti</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nama</th>
                                    <th>Tanggal</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaveRequests as $req)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $req->user->name }}</td>
                                    <td>{{ date('d M Y', strtotime($req->date)) }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-warning-soft text-warning rounded-pill px-3">{{ strtoupper($req->status) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                                @if($leaveRequests->isEmpty())
                                <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada pengajuan izin terbaru</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @elseif($tab === 'payroll')
        <!-- 💰 4. PAYROLL (INI CORE-NYA) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Manajemen Payroll</h5>
                    <p class="small text-muted mb-0">Periode: {{ date('F Y', strtotime($payrollMonth)) }}</p>
                </div>
                <div class="d-flex gap-2">
                    <form action="{{ route('hrd.payroll.generate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="month" value="{{ $payrollMonth }}">
                        <button type="submit" class="btn btn-brand px-4 rounded-pill fw-bold">
                            <i class="fas fa-sync me-2"></i> Generate Draft
                        </button>
                    </form>
                    <button class="btn btn-outline-dark px-4 rounded-pill fw-bold">
                        <i class="fas fa-file-export me-2"></i> Export
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Karyawan</th>
                            <th>Status</th>
                            <th>Gaji Pokok</th>
                            <th>Tunjangan</th>
                            <th>Potongan</th>
                            <th class="fw-bold">Total Diterima</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $p)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $p->user->name }}</div>
                                <small class="text-muted">{{ strtoupper($p->user->role) }}</small>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-3 {{ 
                                    $p->status === 'paid' ? 'bg-success text-white' : 
                                    ($p->status === 'approved' ? 'bg-primary text-white' : 'bg-warning text-dark') 
                                }} uppercase small">{{ $p->status }}</span>
                            </td>
                            <td class="small">Rp {{ number_format($p->basic_salary, 0, ',', '.') }}</td>
                            <td class="small text-success">+ Rp {{ number_format($p->allowances, 0, ',', '.') }}</td>
                            <td class="small text-danger">- Rp {{ number_format($p->deductions, 0, ',', '.') }}</td>
                            <td class="fw-black text-dark">Rp {{ number_format($p->net_salary, 0, ',', '.') }}</td>
                            <td class="text-end pe-4">
                                @if($p->status === 'draft')
                                <form action="{{ route('hrd.payroll.update-status', $p->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn-sm btn-primary rounded-pill px-3">Approve</button>
                                </form>
                                @elseif($p->status === 'approved')
                                <form action="{{ route('hrd.payroll.update-status', $p->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="paid">
                                    <button class="btn btn-sm btn-success rounded-pill px-3">Mark Paid</button>
                                </form>
                                @endif
                                <button class="btn btn-sm btn-light border ms-1"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        @endforeach
                        @if($payrolls->isEmpty())
                        <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data payroll untuk periode ini. Klik "Generate Draft" untuk memulai.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($tab === 'performance')
        <!-- 🧠 5. PERFORMANCE / EVALUASI -->
        <div class="row g-4">
            @foreach($employees as $emp)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3 bg-brand" style="width:50px;height:50px;font-size:1.2rem;">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">{{ $emp->name }}</h6>
                                <small class="text-muted">{{ strtoupper($emp->role) }}</small>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">KPI: 4.5/5</span>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Catatan Performa / Feedback</label>
                        <div class="p-3 bg-light rounded-3 small italic">
                            "{{ $emp->employeeDetail?->performance_notes ?: 'Belum ada catatan evaluasi' }}"
                        </div>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-outline-dark btn-sm rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#editPerformanceModal{{ $emp->id }}">
                            <i class="fas fa-pen-nib me-2"></i> Update Evaluasi
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    @elseif($tab === 'access')
        <!-- 🔐 6. MANAGE LOGIN ACCESS -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Login & Access Control</h5>
                <button class="btn btn-dark px-4 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                    <i class="fas fa-key me-2 text-warning"></i> Buat Akun Baru
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th>Username</th>
                            <th>Role / Permission</th>
                            <th>Last Login</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $user->name }}</td>
                            <td class="small">{{ $user->username }}</td>
                            <td><span class="badge bg-warning-soft text-dark uppercase small">{{ $user->role }}</span></td>
                            <td class="small text-muted">Tadi, 09:15</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#resetPasswordModal{{ $user->id }}">
                                    Reset Password
                                </button>
                                <button class="btn btn-sm btn-light border ms-1"><i class="fas fa-user-shield"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Modals -->
<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('hrd.employee.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Tambah Karyawan & Akun Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control rounded-3" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">NIK (Opsional)</label>
                            <input type="text" name="nik" class="form-control rounded-3">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="username" class="form-control rounded-3" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Password Awal</label>
                            <input type="password" name="password" class="form-control rounded-3" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control rounded-3" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Role</label>
                            <select name="role" class="form-select rounded-3">
                                <option value="kasir">Kasir</option>
                                <option value="waiter">Waiter</option>
                                <option value="kitchen">Kitchen / Dapur</option>
                                <option value="inventory">Inventory / Gudang</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Gaji Pokok</label>
                            <input type="number" name="base_salary" class="form-control rounded-3" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Tanggal Bergabung</label>
                            <input type="date" name="join_date" class="form-control rounded-3">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Sertifikat Kesehatan (Expiry)</label>
                            <input type="date" name="health_certificate_expiry" class="form-control rounded-3">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold">Kontak Darurat</label>
                            <input type="text" name="emergency_contact" class="form-control rounded-3" placeholder="Nama - Nomor HP">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Detail Seragam / Inventaris</label>
                            <textarea name="uniform_details" class="form-control rounded-3" rows="2" placeholder="Contoh: 2 Baju, 1 Apron, 1 Topi"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold">SIMPAN DATA & BUAT AKUN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Settings Jam Shift -->
<div class="modal fade" id="shiftSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('hrd.shift.settings.update') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Konfigurasi Jam Shift Standar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">Atur jam operasional standar untuk setiap kategori shift. Jam ini akan digunakan saat Anda klik cepat di grid.</p>
                    
                    @foreach($shiftSettings as $setting)
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-uppercase">{{ $setting->type }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control border-0 bg-light fw-bold" value="{{ strtoupper(substr($setting->type, 0, 1)) }}" readonly>
                            </div>
                        </div>
                        <div class="col-4">
                            <label class="form-label x-small text-muted">Jam Mulai</label>
                            <input type="time" name="settings[{{ $setting->type }}][start]" class="form-control rounded-3" value="{{ substr($setting->start_time, 0, 5) }}" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label x-small text-muted">Jam Selesai</label>
                            <input type="time" name="settings[{{ $setting->type }}][end]" class="form-control rounded-3" value="{{ substr($setting->end_time, 0, 5) }}" required>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold">SIMPAN PERUBAHAN JAM</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Shift -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('hrd.shift.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Atur Jadwal Shift Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Karyawan</label>
                        <select name="user_id" class="form-select rounded-3" required>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ strtoupper($emp->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal Shift</label>
                        <input type="date" name="date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Mulai</label>
                            <input type="time" name="start_time" class="form-control rounded-3" value="08:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Selesai</label>
                            <input type="time" name="end_time" class="form-control rounded-3" value="16:00" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Tugas / Role Shift</label>
                        <select name="role" class="form-select rounded-3">
                            <option value="kasir">KASIR</option>
                            <option value="waiter">WAITER</option>
                            <option value="kitchen">KITCHEN</option>
                            <option value="inventory">INVENTORY</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold">SIMPAN JADWAL SHIFT</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($employees as $emp)
<!-- Modal Edit Attendance Manual -->
<div class="modal fade" id="editAttendanceModal{{ $emp->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('hrd.attendance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $emp->id }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Koreksi Absensi: {{ $emp->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal</label>
                        <input type="date" name="date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Kehadiran</label>
                        <select name="status" class="form-select rounded-3">
                            <option value="present">HADIR</option>
                            <option value="late">TERLAMBAT</option>
                            <option value="sick">SAKIT</option>
                            <option value="leave">IZIN / CUTI</option>
                            <option value="absent">ALPA (TANPA KETERANGAN)</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Clock In</label>
                            <input type="time" name="clock_in" class="form-control rounded-3">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Clock Out</label>
                            <input type="time" name="clock_out" class="form-control rounded-3">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Catatan Khusus</label>
                        <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Contoh: Ban bocor, sakit gigi, dll"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold">UPDATE DATA ABSENSI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Performance -->
<div class="modal fade" id="editPerformanceModal{{ $emp->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('hrd.performance.update', $emp->id) }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Evaluasi: {{ $emp->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Catatan Performa / Feedback</label>
                        <textarea name="notes" class="form-control rounded-3" rows="5" required>{{ $emp->employeeDetail?->performance_notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 rounded-3 fw-bold">SIMPAN CATATAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetPasswordModal{{ $emp->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('hrd.access.reset-password', $emp->id) }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Reset Password: {{ $emp->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Password Baru</label>
                        <input type="password" name="password" class="form-control rounded-3" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-danger w-100 py-3 rounded-3 fw-bold">KONFIRMASI RESET PASSWORD</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<style>
    .bg-brand { background: var(--brand-gradient); color: #000; }
    .bg-success-soft { background-color: rgba(34, 197, 94, 0.1); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
    .bg-warning-soft { background-color: rgba(251, 191, 36, 0.1); }
    .bg-primary-soft { background-color: rgba(59, 130, 246, 0.1); }
    .text-orange { color: #FF8C00; }
    .fw-black { font-weight: 900; }
    .fw-bold { font-weight: 700; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
    .italic { font-style: italic; }
    .user-avatar { display: flex; align-items: center; justify-content: center; color: #000; font-weight: 800; border-radius: 50%; }
</style>
@endsection
