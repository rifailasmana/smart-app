<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\StaffShift;
use App\Models\EmployeeDetail;
use App\Models\ShiftSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class HRDController extends Controller
{
    public function index(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $tab = $request->get('tab', 'dashboard');
        $today = today();
        $selectedDate = $request->get('date', $today->toDateString());
        
        // 📅 Week/Month Selection
        $viewMode = $request->get('view_mode', 'week');
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->startOfWeek();
        
        if ($viewMode === 'month') {
            $startDate = $startDate->startOfMonth();
            $daysCount = $startDate->daysInMonth;
        } else {
            $daysCount = 7;
        }

        // 🏠 1. DASHBOARD DATA (Exclude Owner & Admin)
        $employeeRoles = ['kasir', 'waiter', 'kitchen', 'dapur', 'inventory', 'manager', 'hrd'];
        
        $totalEmployees = User::where('warung_id', $warungId)
            ->whereIn('role', $employeeRoles)
            ->count();
            
        $presentToday = Attendance::whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->where('date', $today)
            ->where('status', 'present')
            ->count();
            
        $lateToday = Attendance::whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->where('date', $today)
            ->where('status', 'late')
            ->count();
            
        $onLeaveToday = Attendance::whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->where('date', $today)
            ->whereIn('status', ['leave', 'sick'])
            ->count();

        // 👥 2. EMPLOYEE DATA
        $employees = User::with('employeeDetail')
            ->where('warung_id', $warungId)
            ->whereIn('role', $employeeRoles)
            ->get();

        // 🕒 3. ATTENDANCE & SHIFTS (Grid View Logic)
        $weekDates = [];
        for ($i = 0; $i < $daysCount; $i++) {
            $weekDates[] = $startDate->copy()->addDays($i);
        }

        $attendances = Attendance::with('user')
            ->whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->where('date', $selectedDate)
            ->get();
        
        $shifts = StaffShift::with('user')
            ->where('warung_id', $warungId)
            ->whereIn('role', $employeeRoles)
            ->whereBetween('started_at', [$startDate, $startDate->copy()->endOfDay()->addDays($daysCount-1)])
            ->get();

        // ⚙️ Shift Settings
        $shiftSettings = ShiftSetting::where('warung_id', $warungId)->get();
        if ($shiftSettings->isEmpty()) {
            // Seed default if empty
            $defaults = [
                ['type' => 'pagi', 'start_time' => '08:00', 'end_time' => '16:00'],
                ['type' => 'sore', 'start_time' => '16:00', 'end_time' => '00:00'],
                ['type' => 'malam', 'start_time' => '00:00', 'end_time' => '08:00'],
            ];
            foreach ($defaults as $def) {
                ShiftSetting::create(array_merge($def, ['warung_id' => $warungId]));
            }
            $shiftSettings = ShiftSetting::where('warung_id', $warungId)->get();
        }

        // 💰 4. PAYROLL
        $currentMonth = now()->format('Y-m');
        $payrollMonth = $request->get('payroll_month', $currentMonth);
        $payrolls = Payroll::with('user')
            ->whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->where('period_month', $payrollMonth)
            ->get();

        // 📄 7. LEAVE REQUESTS
        $leaveRequests = Attendance::with('user')
            ->whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->whereIn('status', ['leave', 'sick'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // 💡 8. EXPIRING CERTIFICATES (Resto Special)
        $expiringCerts = EmployeeDetail::with('user')
            ->whereHas('user', fn($q) => $q->where('warung_id', $warungId)->whereIn('role', $employeeRoles))
            ->whereNotNull('health_certificate_expiry')
            ->where('health_certificate_expiry', '<', now()->addDays(30))
            ->get();

        // 🔐 6. ACCESS CONTROL
        $users = User::where('warung_id', $warungId)->get(); // Tetap tampilkan semua untuk manajemen akses login

        return view('dashboard.hrd', compact(
            'tab',
            'totalEmployees',
            'presentToday',
            'lateToday',
            'onLeaveToday',
            'employees',
            'attendances',
            'shifts',
            'payrolls',
            'payrollMonth',
            'users',
            'leaveRequests',
            'selectedDate',
            'expiringCerts',
            'weekDates',
            'viewMode',
            'startDate',
            'shiftSettings'
        ));
    }

    public function quickAssignShift(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'shift_type' => 'required' // could be ID or type
        ]);

        $warungId = auth()->user()->warung_id;
        $user = User::findOrFail($request->user_id);

        // Hapus shift lama di tanggal tersebut jika ada
        StaffShift::where('user_id', $request->user_id)
            ->whereDate('started_at', $request->date)
            ->delete();

        if ($request->shift_type === 'off') {
            return back()->with('success', 'Karyawan diliburkan pada tanggal tersebut');
        }

        $setting = ShiftSetting::where('warung_id', $warungId)
            ->where('type', $request->shift_type)
            ->first();

        if (!$setting) {
            return back()->with('error', 'Setting shift tidak ditemukan');
        }

        $start = Carbon::parse($request->date . ' ' . $setting->start_time);
        $end = Carbon::parse($request->date . ' ' . $setting->end_time);
        
        // Handle midnight shift end
        if ($end->lessThan($start)) {
            $end = $end->addDay();
        }

        StaffShift::create([
            'user_id' => $request->user_id,
            'warung_id' => $warungId,
            'role' => $user->role,
            'started_at' => $start,
            'ended_at' => $end
        ]);

        return back()->with('success', 'Shift ' . strtoupper($request->shift_type) . ' berhasil ditetapkan');
    }

    public function updateShiftSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $type => $times) {
            ShiftSetting::updateOrCreate(
                ['warung_id' => auth()->user()->warung_id, 'type' => $type],
                ['start_time' => $times['start'], 'end_time' => $times['end']]
            );
        }

        return back()->with('success', 'Setting jam shift berhasil diperbarui');
    }

    public function storeShift(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'role' => 'required|string'
        ]);

        $startedAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endedAt = Carbon::parse($request->date . ' ' . $request->end_time);

        StaffShift::create([
            'user_id' => $request->user_id,
            'warung_id' => auth()->user()->warung_id,
            'role' => $request->role,
            'started_at' => $startedAt,
            'ended_at' => $endedAt
        ]);

        return back()->with('success', 'Jadwal shift berhasil ditambahkan');
    }

    public function deleteShift(StaffShift $shift)
    {
        $shift->delete();
        return back()->with('success', 'Jadwal shift berhasil dihapus');
    }

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'nik' => 'nullable|string',
            'base_salary' => 'required|numeric|min:0',
            'join_date' => 'nullable|date',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'warung_id' => auth()->user()->warung_id,
        ]);

        EmployeeDetail::create([
            'user_id' => $user->id,
            'nik' => $request->nik,
            'base_salary' => $request->base_salary,
            'join_date' => $request->join_date,
            'health_certificate_expiry' => $request->health_certificate_expiry,
            'emergency_contact' => $request->emergency_contact,
            'uniform_details' => $request->uniform_details,
        ]);

        return back()->with('success', 'Karyawan baru berhasil ditambahkan');
    }

    public function updateEmployee(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string',
            'base_salary' => 'required|numeric|min:0',
        ]);

        $user->update([
            'name' => $request->name,
            'role' => $request->role,
        ]);

        $user->employeeDetail()->update([
            'base_salary' => $request->base_salary,
            'nik' => $request->nik,
            'health_certificate_expiry' => $request->health_certificate_expiry,
            'emergency_contact' => $request->emergency_contact,
            'uniform_details' => $request->uniform_details,
        ]);

        return back()->with('success', 'Data karyawan berhasil diperbarui');
    }

    public function storeAttendance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,sick,leave,late',
            'clock_in' => 'nullable',
            'clock_out' => 'nullable',
        ]);

        Attendance::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            [
                'status' => $request->status,
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'notes' => $request->notes,
            ]
        );

        return back()->with('success', 'Data absensi berhasil disimpan');
    }

    public function generatePayroll(Request $request)
    {
        $month = $request->month;
        $warungId = auth()->user()->warung_id;
        $employees = User::with('employeeDetail')->where('warung_id', $warungId)->get();

        foreach ($employees as $employee) {
            $baseSalary = $employee->employeeDetail->base_salary ?? 0;
            $allowance = $employee->employeeDetail->allowance ?? 0;
            
            // Simple logic for late deductions
            $lateCount = Attendance::where('user_id', $employee->id)
                ->where('date', 'like', "$month%")
                ->where('status', 'late')
                ->count();
            $deductions = $lateCount * 50000; // Example deduction

            Payroll::updateOrCreate(
                ['user_id' => $employee->id, 'period_month' => $month],
                [
                    'basic_salary' => $baseSalary,
                    'allowances' => $allowance,
                    'deductions' => $deductions,
                    'net_salary' => $baseSalary + $allowance - $deductions,
                    'status' => 'draft'
                ]
            );
        }

        return back()->with('success', 'Draft payroll berhasil digenerate');
    }

    public function updatePayrollStatus(Request $request, Payroll $payroll)
    {
        $request->validate(['status' => 'required|in:approved,paid']);
        
        $updateData = ['status' => $request->status];
        if ($request->status === 'approved') $updateData['approved_by'] = auth()->id();
        if ($request->status === 'paid') $updateData['paid_at'] = now();

        $payroll->update($updateData);
        return back()->with('success', 'Status payroll diperbarui');
    }

    public function updatePerformance(Request $request, User $user)
    {
        $request->validate(['notes' => 'required|string']);
        
        $user->employeeDetail()->update(['performance_notes' => $request->notes]);
        return back()->with('success', 'Catatan performa diperbarui');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:6']);
        
        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password user berhasil direset');
    }
}
