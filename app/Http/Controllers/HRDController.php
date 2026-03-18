<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HRDController extends Controller
{
    public function index()
    {
        $warungId = auth()->user()->warung_id;
        $employees = User::where('warung_id', $warungId)->with('warung')->get();
        
        return view('dashboard.hrd', compact('employees'));
    }

    public function attendance(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $date = $request->get('date', today()->toDateString());
        $attendances = Attendance::whereHas('user', function($q) use ($warungId) {
            $q->where('warung_id', $warungId);
        })->where('date', $date)->get();

        return view('dashboard.hrd.attendance', compact('attendances', 'date'));
    }

    public function payroll(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $month = $request->get('month', now()->format('Y-m'));
        $payrolls = Payroll::whereHas('user', function($q) use ($warungId) {
            $q->where('warung_id', $warungId);
        })->where('period_month', $month)->get();

        return view('dashboard.hrd.payroll', compact('payrolls', 'month'));
    }

    public function generatePayroll(Request $request)
    {
        $month = $request->month; // YYYY-MM
        $warungId = auth()->user()->warung_id;
        $employees = User::where('warung_id', $warungId)->get();

        foreach ($employees as $employee) {
            // Basic logic: base salary + allowances - deductions
            // This can be more complex based on attendance
            Payroll::updateOrCreate(
                ['user_id' => $employee->id, 'period_month' => $month],
                [
                    'basic_salary' => 3000000, // Example
                    'allowances' => 500000,
                    'deductions' => 0,
                    'net_salary' => 3500000,
                    'status' => 'draft'
                ]
            );
        }

        return back()->with('success', 'Payroll draft generated for ' . $month);
    }

    public function shifts(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $date = $request->get('date', today()->toDateString());
        
        $shifts = \App\Models\StaffShift::with('user')
            ->where('warung_id', $warungId)
            ->whereDate('started_at', $date)
            ->get();
            
        $employees = User::where('warung_id', $warungId)->get();
        
        return view('dashboard.hrd.shifts', compact('shifts', 'date', 'employees'));
    }

    public function storeShift(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'date' => 'required|date',
        ]);

        $warungId = auth()->user()->warung_id;
        $startedAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endedAt = Carbon::parse($request->date . ' ' . $request->end_time);

        \App\Models\StaffShift::create([
            'user_id' => $request->user_id,
            'warung_id' => $warungId,
            'role' => $request->role,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);

        return back()->with('success', 'Jadwal shift berhasil ditambahkan');
    }

    public function destroyShift($id)
    {
        $shift = \App\Models\StaffShift::findOrFail($id);
        $shift->delete();
        
        return back()->with('success', 'Jadwal shift berhasil dihapus');
    }
}
