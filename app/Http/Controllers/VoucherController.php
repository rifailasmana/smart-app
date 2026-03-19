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
            'count' => 'required|integer|min:1|max:50', // Number of vouchers to generate
        ]);

        $warungId = auth()->user()->warung_id;
        $vouchers = [];

        for ($i = 0; $i < $validated['count']; $i++) {
            $code = 'MAJAR-' . strtoupper(Str::random(6));
            
            // Ensure uniqueness
            while (Voucher::where('code', $code)->exists()) {
                $code = 'MAJAR-' . strtoupper(Str::random(6));
            }

            $vouchers[] = Voucher::create([
                'warung_id' => $warungId,
                'code' => $code,
                'type' => $validated['type'],
                'value' => $validated['value'],
                'category_restriction' => $validated['category_restriction'],
                'is_used' => false,
            ]);
        }

        return redirect()->back()->with('success', count($vouchers) . ' Voucher berhasil dibuat!');
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
