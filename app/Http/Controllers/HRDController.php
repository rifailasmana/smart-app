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
}
