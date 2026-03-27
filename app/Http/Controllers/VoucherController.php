<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::where('warung_id', auth()->user()->warung_id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return view('manage.vouchers.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'category_restriction' => 'nullable|string',
            'count' => 'required|integer|min:1|max:50',
            'duration_hours' => 'nullable|integer|min:1',
        ]);

        $warungId = auth()->user()->warung_id;
        if (!$warungId) {
            return redirect()->back()->with('error', 'Gagal simpan ke database: Warung ID tidak ditemukan.');
        }

        $vouchersCreated = 0;
        $duration = $validated['duration_hours'] ?? 1;

        try {
            for ($i = 0; $i < $validated['count']; $i++) {
                $code = 'MAJAR-' . strtoupper(Str::random(6));
                
                while (Voucher::where('code', $code)->exists()) {
                    $code = 'MAJAR-' . strtoupper(Str::random(6));
                }

                Voucher::create([
                    'warung_id' => $warungId,
                    'code' => $code,
                    'type' => $validated['type'],
                    'value' => $validated['value'],
                    'category_restriction' => $validated['category_restriction'],
                    'is_used' => 0,
                    'expired_at' => now()->addHours($duration),
                ]);
                $vouchersCreated++;
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal simpan ke database: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', $vouchersCreated . ' Voucher berhasil dibuat!');
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->warung_id !== auth()->user()->warung_id) {
            return abort(403);
        }
        $voucher->delete();
        return redirect()->back()->with('success', 'Voucher berhasil dihapus.');
    }
}
