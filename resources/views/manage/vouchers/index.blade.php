@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">Voucher Generator</h1>
            <p class="text-gray-500 font-bold">Kelola voucher diskon one-time use untuk Majar Signature.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-xl font-bold shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Generator Form -->
        <div class="lg:col-span-1">
            <div class="bg-white border-2 border-black rounded-[2rem] p-8 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <h3 class="text-xl font-black mb-6 uppercase tracking-widest flex items-center gap-2">
                    <i class="bi bi-ticket-perforated-fill text-orange-500"></i>
                    Generate Baru
                </h3>
                
                <form action="{{ route('manage.vouchers.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Tipe Diskon</label>
                        <select name="type" class="w-full bg-gray-50 border-2 border-black rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-0 focus:border-orange-500">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Nilai Diskon</label>
                        <input type="number" name="value" required class="w-full bg-gray-50 border-2 border-black rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-0 focus:border-orange-500" placeholder="Misal: 10 atau 50000">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Batasan Kategori (Opsional)</label>
                        <input type="text" name="category_restriction" class="w-full bg-gray-50 border-2 border-black rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-0 focus:border-orange-500" placeholder="Contoh: Signature">
                        <p class="text-[9px] text-gray-400 mt-1 font-bold italic">*Kosongkan jika berlaku untuk semua kategori.</p>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Jumlah Voucher</label>
                        <input type="number" name="count" value="1" min="1" max="50" class="w-full bg-gray-50 border-2 border-black rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-0 focus:border-orange-500">
                    </div>

                    <button type="submit" class="w-full bg-black text-white font-black py-4 rounded-xl hover:bg-orange-500 transition-all active:scale-95 shadow-lg shadow-black/10">
                        GENERATE VOUCHER
                    </button>
                </form>
            </div>
        </div>

        <!-- Voucher List -->
        <div class="lg:col-span-2">
            <div class="bg-white border-2 border-black rounded-[2rem] overflow-hidden shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-black">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Kode Voucher</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Diskon</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Kategori</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($vouchers as $v)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-black text-orange-500 font-mono tracking-wider">{{ $v->code }}</td>
                                <td class="px-6 py-4 font-bold">
                                    {{ $v->type === 'percentage' ? $v->value . '%' : 'Rp ' . number_format($v->value, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($v->is_used)
                                        <span class="bg-red-100 text-red-600 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-widest">Terpakai</span>
                                    @else
                                        <span class="bg-green-100 text-green-600 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-widest">Aktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-500 uppercase text-[10px]">
                                    {{ $v->category_restriction ?: 'Semua' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('manage.vouchers.destroy', $v->id) }}" method="POST" onsubmit="return confirm('Hapus voucher ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                            <i class="bi bi-trash-fill text-xl"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4 bg-gray-50 border-t-2 border-black">
                    {{ $vouchers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
