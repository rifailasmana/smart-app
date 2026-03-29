@extends('layouts.terminal')

@section('title', 'Kasir - Majar Signature')
@section('terminal_role', 'KASIR')

@section('header_extra')
    {{-- Clock and Status are now handled by layout --}}
@endsection

@section('content')
    <div class="w-full h-full" id="kasir-root"></div>
@endsection

@section('extra_js')
    <style>
        .fluid-text-h1 {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
        }
        .fluid-text-h2 {
            font-size: clamp(1.25rem, 3vw, 2rem);
        }
        .fluid-text-body {
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        .fluid-icon {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
        }
        .fluid-card-padding {
            padding: clamp(0.75rem, 1.5vw, 1rem);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
    <script type="text/babel">
    const { useState, useEffect, useMemo, useCallback } = React;
    // Fix for deployments where Laravel is served under a URL prefix (e.g. /smart-app/public).
    // Compute prefix from current path (/terminal/kasir) to ensure we call:
    //   <prefix>/terminal/...
    const API_BASE = (() => {
        const origin = window.location.origin;
        const path = window.location.pathname;
        const prefix = path.replace(/\/terminal\/kasir\/?$/, '');
        return prefix ? origin + prefix : origin;
    })();
    const api = (path) => `${API_BASE}${path}`;

    // --- Components ---

    const SwipeableItem = ({ children, onSwipe, threshold = 100 }) => {
        const [offsetX, setOffsetX] = React.useState(0);
        const [startX, setStartX] = React.useState(0);

        const handleTouchStart = (e) => {
            setStartX(e.touches[0].clientX);
        };

        const handleTouchMove = (e) => {
            const currentX = e.touches[0].clientX;
            const diff = currentX - startX;
            if (diff < 0) { // Only swipe left
                setOffsetX(diff);
            }
        };

        const handleTouchEnd = () => {
            if (offsetX < -threshold) {
                onSwipe();
            }
            setOffsetX(0);
        };

        return (
            <div 
                className="relative overflow-hidden rounded-2xl bg-red-500"
                onTouchStart={handleTouchStart}
                onTouchMove={handleTouchMove}
                onTouchEnd={handleTouchEnd}
            >
                <div 
                    className="bg-white transition-transform duration-200 flex items-center"
                    style={ { transform: 'translateX(' + offsetX + 'px)' } }
                >
                    {children}
                </div>
                <div className="absolute inset-y-0 right-0 w-20 flex items-center justify-center text-white pointer-events-none">
                    <i className="bi bi-trash3-fill text-xl"></i>
                </div>
            </div>
        );
    };

    const Numpad = ({ value, onChange, onConfirm, label = "Bayar" }) => {
        const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', '⌫'];
        
        const handleClick = (key) => {
            if (key === 'C') onChange(0);
            else if (key === '⌫') onChange(Math.floor(value / 10));
            else {
                const newValue = parseInt(value.toString() + key);
                if (!isNaN(newValue)) onChange(newValue);
            }
        };

        return (
            <div className="flex flex-col gap-3">
                <div className="grid grid-cols-3 gap-2">
                    {keys.map(key => (
                        <button
                            key={key}
                            onClick={() => handleClick(key)}
                            className={'h-14 rounded-xl font-black text-xl transition-all active:scale-95 active:bg-orange-500 active:text-white ' + (key === 'C' ? 'bg-red-50 text-red-500' : 'bg-gray-100 text-gray-900')}
                        >
                            {key}
                        </button>
                    ))}
                </div>
                <button
                    onClick={onConfirm}
                    className="w-full py-4 bg-gradient-to-r from-orange-500 to-yellow-400 text-white rounded-2xl font-black text-lg shadow-xl shadow-orange-500/30 active:scale-95 transition-all"
                >
                    {label}
                </button>
            </div>
        );
    };

    const Toast = ({ message, type = 'success', onClose }) => {
        useEffect(() => {
            const timer = setTimeout(onClose, 3000);
            return () => clearTimeout(timer);
        }, [onClose]);

        return (
            <div className={'fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] animate-in slide-in-from-bottom duration-300'}>
                <div className={'px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 ' + (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white')}>
                    <i className={'bi ' + (type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill')}></i>
                    <span className="font-bold text-sm">{message}</span>
                </div>
            </div>
        );
    };

    const ReceiptPreviewModal = ({ order, onPrint, onClose, onShare }) => {
        const subtotal = order.items.reduce((acc, item) => acc + (item.price * item.qty), 0);
        const discount = order.discount_amount || 0;
        const total = order.total || (subtotal - discount);

        return (
            <div className="fixed inset-0 z-[10000] flex items-center justify-center p-2 sm:p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-[95vw] sm:max-w-md lg:max-w-lg rounded-3xl sm:rounded-[2rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300 flex flex-col max-h-[96vh] sm:max-h-[90vh]">
                    <div className="p-3 sm:p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 flex-shrink-0">
                        <h3 className="text-sm sm:text-base font-black text-gray-900 tracking-tight">Preview Struk</h3>
                        <button onClick={onClose} className="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-white shadow-sm flex items-center justify-center text-gray-400 active:scale-95 transition-all"><i className="bi bi-x-lg text-base sm:text-lg"></i></button>
                    </div>

                    <div className="flex-1 overflow-y-auto p-3 sm:p-4 custom-scrollbar bg-gray-50/30">
                        {/* Compact Thermal Receipt Design */}
                        <div className="bg-white p-3 sm:p-4 shadow-sm border border-gray-100 mx-auto w-full max-w-[26rem] font-mono text-[10px] sm:text-[11px] text-gray-800 leading-tight receipt-content">
                            <div className="text-center mb-3">
                                <h4 className="font-black text-[11px] sm:text-xs uppercase tracking-tighter mb-0.5">{{ $warung->name ?? 'MAJAR POS' }}</h4>
                                <p className="text-[8px] sm:text-[9px] text-gray-400">{{ $warung->address ?? 'Alamat Warung Majar' }}</p>
                                <p className="text-[8px] sm:text-[9px] text-gray-400">Telp: {{ $warung->phone ?? '-' }}</p>
                                <div className="border-b border-dashed border-gray-200 my-2"></div>
                            </div>

                            <div className="space-y-0.5 mb-2">
                                <div className="flex justify-between"><span>TGL: {new Date().toLocaleDateString('id-ID')}</span> <span>JAM: {new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}</span></div>
                                <div className="flex justify-between"><span>KASIR: {{ Auth::user()->name }}</span> <span>#{order.code || order.id}</span></div>
                                <div className="flex justify-between"><span>MEJA: {order.table?.name || 'TA'}</span> <span>CUST: {order.customer_name || 'Guest'}</span></div>
                            </div>

                            <div className="border-b border-dashed border-gray-200 mb-2"></div>

                            <div className="space-y-1.5 mb-3">
                                {order.items.map((item, idx) => (
                                    <div key={idx}>
                                        <div className="flex justify-between">
                                            <span className="font-bold truncate pr-2">{item.menu_name}</span>
                                            <span className="flex-shrink-0">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</span>
                                        </div>
                                        <div className="text-[8px] sm:text-[9px] text-gray-400 pl-2">{item.qty} x {new Intl.NumberFormat('id-ID').format(item.price)}</div>
                                    </div>
                                ))}
                            </div>

                            <div className="border-b border-dashed border-gray-200 mb-2"></div>

                            <div className="space-y-0.5 text-right mb-3">
                                <div className="flex justify-between"><span>SUBTOTAL</span> <span>Rp {new Intl.NumberFormat('id-ID').format(subtotal)}</span></div>
                                {discount > 0 && <div className="flex justify-between text-red-500"><span>DISKON</span> <span>- Rp {new Intl.NumberFormat('id-ID').format(discount)}</span></div>}
                                <div className="flex justify-between font-black text-[11px] sm:text-xs pt-1 border-t border-dashed border-gray-100 mt-1">
                                    <span>TOTAL</span> <span>Rp {new Intl.NumberFormat('id-ID').format(total)}</span>
                                </div>
                            </div>

                            <div className="text-center mt-4">
                                <p className="text-[8px] sm:text-[9px] font-bold italic mb-0.5">Terima Kasih Atas Kunjungan Anda</p>
                                <p className="text-[7px] sm:text-[8px] text-gray-400 uppercase tracking-widest">Powered by Majar POS</p>
                            </div>
                        </div>
                    </div>

                    <div className="p-3 sm:p-4 border-t border-gray-100 bg-white grid grid-cols-2 gap-2 flex-shrink-0">
                        <button 
                            onClick={onShare}
                            className="flex items-center justify-center gap-1.5 sm:gap-2 py-2 sm:py-2.5 bg-blue-50 text-blue-600 rounded-xl font-black text-[10px] sm:text-[11px] uppercase tracking-wider sm:tracking-widest active:scale-95 transition-all"
                        >
                            <i className="bi bi-whatsapp"></i> Digital
                        </button>
                        <button 
                            onClick={onPrint}
                            className="flex items-center justify-center gap-1.5 sm:gap-2 py-2 sm:py-2.5 bg-orange-500 text-white rounded-xl font-black text-[10px] sm:text-[11px] uppercase tracking-wider sm:tracking-widest shadow-lg shadow-orange-500/20 active:scale-95 transition-all"
                        >
                            <i className="bi bi-printer"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>
        );
    };

    const PaymentModal = ({ order, onConfirm, onClose, onShowToast }) => {
        // --- Defensive Check: Fallback UI ---
        if (!order) {
            return (
                <div className="fixed inset-0 z-[9000] flex items-center justify-center bg-black/60 backdrop-blur-sm">
                    <div className="bg-white p-8 rounded-[2rem] flex flex-col items-center gap-4 shadow-2xl">
                        <div className="w-12 h-12 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                        <p className="font-black text-gray-900 uppercase tracking-widest text-[10px]">Memuat Data Pesanan...</p>
                    </div>
                </div>
            );
        }

        const [method, setPaymentMethod] = useState('Tunai');
        const [voucherCode, setVoucherCode] = useState('');
        const [activeVoucher, setActiveVoucher] = useState(null);
        const [checkingVoucher, setCheckingVoucher] = useState(false);
        const [amountPaid, setAmountPaid] = useState(0); // Strict numeric init (0)
        const [processing, setProcessing] = useState(false);
        const [showQR, setShowQR] = useState(false);

        const handleCheckVoucher = async () => {
            if (!voucherCode) return;
            setCheckingVoucher(true);
            try {
                const res = await fetch(api('/terminal/check-voucher'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: voucherCode })
                });
                const data = await res.json();
                if (res.ok) {
                    setActiveVoucher(data);
                    onShowToast('Voucher Berhasil Dipasang!', 'success');
                } else {
                    onShowToast(data.error || 'Voucher tidak valid', 'error');
                    setActiveVoucher(null);
                }
            } catch (e) {
                onShowToast('Gagal mengecek voucher', 'error');
            } finally {
                setCheckingVoucher(false);
            }
        };

        const discountAmount = useMemo(() => {
            if (!activeVoucher) return 0;
            if (activeVoucher.type === 'percentage') {
                return (order.total * (activeVoucher.value / 100));
            }
            return activeVoucher.value;
        }, [order.total, activeVoucher]);

        const finalTotal = Math.max(0, order.total - discountAmount);

        useEffect(() => {
            if (method !== 'Tunai') setAmountPaid(finalTotal);
            else setAmountPaid(0); // Reset when switching to Tunai for keypad input
        }, [finalTotal, method]);

        const handleFinalize = async () => {
            if (method === 'QRIS' && !showQR) {
                setShowQR(true);
                return;
            }
            setProcessing(true);
            await onConfirm(method, amountPaid, discountAmount, activeVoucher?.code || '', activeVoucher?.id || null);
            setProcessing(false);
        };

        if (showQR) {
            return (
                <div className="fixed inset-0 z-[9000] flex items-center justify-center p-8 bg-black/80 backdrop-blur-md animate-in fade-in duration-300">
                    <div className="bg-white w-full max-w-xl rounded-[3rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                        <div className="p-12 flex flex-col items-center text-center">
                            <div className="w-20 h-20 bg-orange-100 text-orange-500 rounded-3xl flex items-center justify-center mb-6">
                                <i className="bi bi-qr-code-scan text-4xl"></i>
                            </div>
                            <h2 className="text-3xl font-black text-gray-900 mb-2 uppercase tracking-tight">Scan QRIS</h2>
                            <p className="text-gray-400 font-medium mb-8">Silakan scan kode QR di bawah ini</p>
                            
                            <div className="relative group mb-8">
                                <div className="absolute -inset-4 bg-gradient-to-tr from-orange-500 to-yellow-400 rounded-[2.5rem] blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                                <div className="relative bg-white p-6 rounded-[2rem] shadow-xl">
                                    <img
                                        src={'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=MAJAR-POS-' + order.id + '-' + finalTotal}
                                        alt="QRIS Dummy"
                                        className="w-48 h-48"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-3 mb-8 bg-blue-50 text-blue-600 px-6 py-3 rounded-2xl w-full">
                                <i className="bi bi-info-circle-fill"></i>
                                <p className="text-[10px] font-bold uppercase tracking-widest leading-relaxed">Silakan scan dan lakukan pembayaran. Kasir akan melakukan konfirmasi manual.</p>
                            </div>

                            <button
                                disabled={processing}
                                onClick={handleFinalize}
                                className="w-full py-5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-[2rem] font-black text-xl shadow-xl shadow-green-500/30 transition-all active:scale-95"
                            >
                                {processing ? 'Memverifikasi...' : 'Konfirmasi Sudah Bayar'}
                            </button>
                            <button
                                onClick={() => setShowQR(false)}
                                className="mt-4 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors"
                            >
                                Ganti Metode Pembayaran
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return (
            <div className="fixed inset-0 z-[9000] flex items-center justify-center p-2 sm:p-4 lg:p-5 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-6xl h-[calc(100vh-1rem)] sm:h-[calc(100vh-2rem)] lg:h-[calc(100vh-2.5rem)] max-h-screen rounded-3xl sm:rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300 flex flex-col border border-white/20">
                    
                    {/* Header Compact */}
                    <div className="px-4 sm:px-5 lg:px-6 py-2.5 sm:py-3 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
                        <div className="flex items-center gap-4">
                            <h2 className="text-sm sm:text-base lg:text-lg font-black text-gray-900 uppercase tracking-tight">Majar Signature Terminal</h2>
                            <div className="h-4 w-px bg-gray-200"></div>
                            <span className="text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-widest">#{order.code}</span>
                        </div>
                        <button onClick={onClose} className="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all">
                            <i className="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <div className="flex-1 flex flex-row overflow-hidden bg-white">
                        
                        {/* 1. Left Section: Billing (30%) - Compact, No Menu List */}
                        <div className="w-[30%] min-w-0 p-3 sm:p-4 lg:p-5 flex flex-col border-r border-gray-100 bg-gray-50/30">
                            <div className="space-y-3 sm:space-y-4">
                                <div className="bg-white p-3 sm:p-4 lg:p-5 rounded-2xl lg:rounded-3xl border border-gray-100 shadow-sm">
                                    <p className="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Rincian Tagihan</p>
                                    <div className="space-y-2 mb-4">
                                        <div className="flex justify-between text-[11px] font-bold text-gray-500 uppercase">
                                            <span>Subtotal</span>
                                            <span>Rp {new Intl.NumberFormat('id-ID').format(order.total)}</span>
                                        </div>
                                        {discountAmount > 0 && (
                                            <div className="flex justify-between text-[11px] font-bold text-green-600 uppercase">
                                                <span>Diskon</span>
                                                <span>- Rp {new Intl.NumberFormat('id-ID').format(discountAmount)}</span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="border-t-2 border-dashed border-gray-100 pt-3 sm:pt-4">
                                        <p className="text-[9px] font-black text-orange-500 uppercase tracking-[0.2em] mb-1">Total Bayar</p>
                                        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-black text-orange-500 tracking-tighter leading-none">
                                            Rp {new Intl.NumberFormat('id-ID').format(finalTotal)}
                                        </h1>
                                    </div>
                                </div>

                                {/* Voucher Center - Slimmed Down */}
                                <div className="bg-white p-3 sm:p-4 lg:p-5 rounded-2xl lg:rounded-3xl border border-gray-100 shadow-sm">
                                    <label className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 block">Input Kupon/Voucher</label>
                                    <div className="flex gap-2">
                                        <input
                                            type="text"
                                            value={voucherCode}
                                            onChange={(e) => setVoucherCode(e.target.value.toUpperCase())}
                                            placeholder="KODE..."
                                            className="flex-1 min-w-0 bg-gray-50 border-2 border-gray-100 rounded-xl px-3 sm:px-4 py-2 font-black text-xs sm:text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all uppercase placeholder:text-gray-200"
                                        />
                                        <button
                                            onClick={handleCheckVoucher}
                                            disabled={checkingVoucher || !voucherCode}
                                            className="px-3 sm:px-4 py-2 bg-gray-900 text-white rounded-xl font-black text-[9px] uppercase tracking-wider sm:tracking-widest active:scale-95 disabled:opacity-50 transition-all"
                                        >
                                            {checkingVoucher ? '...' : 'Apply'}
                                        </button>
                                    </div>
                                    {activeVoucher && (
                                        <div className="mt-3 p-3 bg-green-50 rounded-xl border border-green-100 flex justify-between items-center animate-in slide-in-from-top-2">
                                            <div className="flex items-center gap-2">
                                                <i className="bi bi-patch-check-fill text-green-500 text-lg"></i>
                                                <span className="text-[10px] font-black text-green-700 uppercase">Voucher Terpasang</span>
                                            </div>
                                            <button onClick={() => { setActiveVoucher(null); setVoucherCode(''); }} className="text-gray-400 hover:text-red-500">
                                                <i className="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="mt-auto bg-blue-50/50 p-3 sm:p-4 rounded-2xl border border-blue-100">
                                <div className="flex items-start gap-2">
                                    <i className="bi bi-info-circle-fill text-blue-500 text-xs"></i>
                                    <p className="text-[8px] font-bold text-blue-600 uppercase leading-relaxed tracking-wider">Hapus daftar menu untuk hemat memori & fokus pada pembayaran.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2. Middle Section: Method (25%) - Large Buttons */}
                        <div className="w-[25%] min-w-0 p-3 sm:p-4 lg:p-5 border-r border-gray-100 flex flex-col bg-white">
                            <h3 className="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 sm:mb-4 lg:mb-5 text-center">Pilih Metode</h3>
                            <div className="grid grid-cols-1 gap-2 sm:gap-3">
                                {[
                                    { id: 'Tunai', label: 'CASH', icon: 'bi-cash-stack' },
                                    { id: 'QRIS', label: 'QRIS', icon: 'bi-qr-code-scan' },
                                    { id: 'EDC', label: 'CARD', icon: 'bi-credit-card-2-front' },
                                    { id: 'INVOICE', label: 'INVOICE', icon: 'bi-file-earmark-text' }
                                ].map(m => (
                                    <button
                                        key={m.id}
                                        onClick={() => setPaymentMethod(m.id)}
                                        className={'flex items-center gap-2 sm:gap-3 lg:gap-4 px-3 sm:px-4 lg:px-5 py-2.5 sm:py-3 lg:py-4 rounded-2xl lg:rounded-[1.5rem] border-2 transition-all active:scale-95 ' + (method === m.id ? 'border-orange-500 bg-orange-50 text-orange-600 shadow-xl shadow-orange-500/10' : 'border-gray-50 bg-gray-50/50 text-gray-400 hover:border-gray-200')}
                                    >
                                        <div className={'w-9 h-9 sm:w-10 sm:h-10 lg:w-12 lg:h-12 rounded-xl lg:rounded-2xl flex items-center justify-center transition-colors flex-shrink-0 ' + (method === m.id ? 'bg-orange-500 text-white' : 'bg-white text-gray-300')}>
                                            <i className={'bi ' + m.icon + ' text-lg sm:text-xl lg:text-2xl'}></i>
                                        </div>
                                        <span className="text-[11px] sm:text-xs lg:text-sm font-black tracking-[0.08em] lg:tracking-[0.1em] truncate">{m.label}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* 3. Right Section: Interaction (45%) - Compact Keypad & Horizontal Display */}
                        <div className="w-[45%] min-w-0 p-3 sm:p-4 lg:p-5 flex flex-col bg-white overflow-hidden no-scrollbar">
                            <div className="flex-1 flex flex-col">
                                {method === 'Tunai' ? (
                                    <div className="flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-300">
                                        {/* Horizontal Display Box - Black */}
                                        <div className="bg-gray-900 rounded-2xl lg:rounded-[2rem] p-3 sm:p-4 lg:p-5 shadow-2xl mb-3 sm:mb-4 lg:mb-5">
                                            <div className="flex justify-between items-center gap-3 sm:gap-5 lg:gap-8">
                                                <div className="flex-1">
                                                    <p className="text-[8px] font-black text-orange-500 uppercase tracking-widest mb-1">Uang Diterima</p>
                                                    <div className="text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tighter">
                                                        Rp {new Intl.NumberFormat('id-ID').format(amountPaid)}
                                                    </div>
                                                </div>
                                                <div className="h-10 w-px bg-gray-800"></div>
                                                <div className="flex-1 text-right">
                                                    <p className="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Kembalian</p>
                                                    <div className={`text-xl sm:text-2xl lg:text-3xl font-black ${amountPaid >= finalTotal ? 'text-green-400' : 'text-red-400/20'}`}>
                                                        Rp {new Intl.NumberFormat('id-ID').format(Math.max(0, amountPaid - finalTotal))}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Keypad Padat & Skala Kecil */}
                                        <div className="grid grid-cols-3 gap-2 mb-3 sm:mb-4 max-w-[300px] sm:max-w-[320px] mx-auto w-full">
                                            {['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', '⌫'].map(key => (
                                                <button
                                                    key={key}
                                                    onClick={() => {
                                                        if (key === 'C') setAmountPaid(0);
                                                        else if (key === '⌫') setAmountPaid(Math.floor(amountPaid / 10));
                                                        else {
                                                            const newVal = parseInt(amountPaid.toString() + key);
                                                            if (newVal <= 999999999) setAmountPaid(newVal || 0);
                                                        }
                                                    }}
                                                    className={'h-10 sm:h-11 lg:h-12 rounded-xl lg:rounded-2xl font-black text-base sm:text-lg lg:text-xl transition-all active:scale-90 ' + (key === 'C' ? 'bg-red-50 text-red-500' : key === '⌫' ? 'bg-gray-100 text-gray-600' : 'bg-white border-2 border-gray-100 hover:border-orange-500 hover:text-orange-500 text-gray-900 shadow-sm')}
                                                >
                                                    {key}
                                                </button>
                                            ))}
                                            <button
                                                onClick={() => setAmountPaid(finalTotal)}
                                                className="col-span-3 h-10 sm:h-11 lg:h-12 bg-orange-100 text-orange-600 rounded-xl lg:rounded-2xl font-black text-[10px] sm:text-xs tracking-wider sm:tracking-widest hover:bg-orange-200 active:scale-95 transition-all flex items-center justify-center gap-2"
                                            >
                                                <i className="bi bi-lightning-fill"></i> UANG PAS (Rp {new Intl.NumberFormat('id-ID').format(finalTotal)})
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="flex-1 flex flex-col justify-center items-center text-center p-3 sm:p-4 lg:p-6 animate-in zoom-in duration-300">
                                        <div className={`w-14 h-14 sm:w-16 sm:h-16 lg:w-20 lg:h-20 rounded-2xl lg:rounded-[2rem] flex items-center justify-center mb-3 sm:mb-4 shadow-xl border-4 ${
                                            method === 'QRIS' ? 'bg-orange-50 text-orange-500 border-orange-100' :
                                            method === 'INVOICE' ? 'bg-blue-50 text-blue-500 border-blue-100' :
                                            'bg-purple-50 text-purple-500 border-purple-100'
                                        }`}>
                                            <i className={`bi ${
                                                method === 'QRIS' ? 'bi-qr-code-scan' :
                                                method === 'INVOICE' ? 'bi-file-earmark-text' :
                                                'bi-credit-card-2-front'
                                            } text-2xl sm:text-3xl lg:text-4xl`}></i>
                                        </div>
                                        <h3 className="text-base sm:text-lg lg:text-xl font-black text-gray-900 tracking-tight mb-2 uppercase">{method}</h3>
                                        <p className="text-gray-400 font-medium text-[9px] sm:text-[10px] mb-4 sm:mb-6 lg:mb-8 max-w-[240px] leading-relaxed uppercase tracking-wider sm:tracking-widest">
                                            {method === 'QRIS' ? 'Klik Selesaikan untuk generate QR Code.' :
                                             method === 'INVOICE' ? 'Tagihan akan diproses sebagai Invoice Piutang.' :
                                             'Siapkan mesin EDC untuk transaksi kartu.'}
                                        </p>
                                    </div>
                                )}

                                {/* Action Button: Hijau & Paling Bawah */}
                                <button
                                    disabled={processing || (method === 'Tunai' && amountPaid < finalTotal)}
                                    onClick={handleFinalize}
                                    className="w-full py-3 sm:py-4 lg:py-5 bg-green-600 hover:bg-green-700 text-white rounded-2xl lg:rounded-[2rem] font-black text-sm sm:text-base lg:text-xl shadow-2xl shadow-green-600/30 active:scale-95 transition-all disabled:opacity-30 disabled:grayscale uppercase tracking-[0.12em] sm:tracking-[0.16em] lg:tracking-[0.2em] mt-auto border-b-4 lg:border-b-8 border-green-800"
                                >
                                    {processing ? 'Memproses...' : 'Selesaikan Pesanan'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const VoidOrderModal = ({ order, onVoid, onClose }) => {
        const [reason, setReason] = useState('');
        const [pin, setPin] = useState('');
        const [processing, setProcessing] = useState(false);

        const handleSubmit = async () => {
            if (!reason.trim() || !pin.trim()) return;
            setProcessing(true);
            await onVoid(reason, pin);
            setProcessing(false);
        };

        return (
            <div className="fixed inset-0 z-[9000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-sm rounded-[2rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                    <div className="p-6">
                        <h2 className="text-xl font-black text-gray-900 tracking-tight mb-1">Void Pesanan</h2>
                        <p className="text-gray-400 font-medium text-xs leading-relaxed mb-5">Masukkan alasan void dan PIN manager.</p>

                        <div className="mb-4">
                            <label className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Alasan</label>
                            <input value={reason} onChange={(e) => setReason(e.target.value)} className="w-full bg-gray-50 rounded-xl p-3 text-sm" placeholder="Alasan void..." />
                        </div>

                        <div className="mb-6">
                            <label className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 block">PIN Manager</label>
                            <input type="password" value={pin} onChange={(e) => setPin(e.target.value)} className="w-full bg-gray-50 rounded-xl p-3 text-sm" placeholder="PIN..." />
                        </div>

                        <div className="flex gap-2">
                            <button onClick={onClose} className="flex-1 py-3 bg-gray-100 text-gray-600 rounded-xl font-black text-xs">Batal</button>
                            <button disabled={processing} onClick={handleSubmit} className="flex-1 py-3 bg-red-500 text-white rounded-xl font-black text-xs">{processing ? '...' : 'Void Pesanan'}</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const VoidItemModal = ({ target, onClose, onSubmit }) => {
        const [qty, setQty] = useState(target?.item?.qty || 1);
        const [reason, setReason] = useState('');
        const [processing, setProcessing] = useState(false);

        const handle = async () => {
            if (!qty || qty < 1) return;
            setProcessing(true);
            await onSubmit(target.orderId, target.item.id, qty, reason);
            setProcessing(false);
        };

        return (
            <div className="fixed inset-0 z-[9000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-sm rounded-[2rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300 text-black">
                    <div className="p-6">
                        <h2 className="text-xl font-black tracking-tight mb-1">Void Item</h2>
                        <p className="text-gray-500 font-medium text-xs mb-5">Item: {target?.item?.menu_name} (maks {target?.item?.qty})</p>

                        <div className="mb-4">
                            <label className="text-[9px] font-black text-black uppercase tracking-widest mb-2 block">Jumlah</label>
                            <input type="number" min="1" max={target?.item?.qty} value={qty} onChange={(e)=> setQty(Math.max(1, Math.min(target?.item?.qty || 1, parseInt(e.target.value || 0))))} className="w-full bg-gray-50 rounded-xl p-3 text-sm text-black" />
                        </div>

                        <div className="mb-6">
                            <label className="text-[9px] font-black text-black uppercase tracking-widest mb-2 block">Alasan</label>
                            <input value={reason} onChange={(e) => setReason(e.target.value)} className="w-full bg-gray-50 rounded-xl p-3 text-sm text-black" placeholder="Alasan..." />
                        </div>

                        <div className="flex gap-2">
                            <button onClick={onClose} className="flex-1 py-3 bg-gray-100 text-gray-600 rounded-xl font-black text-xs">Batal</button>
                            <button disabled={processing} onClick={handle} className="flex-1 py-3 bg-red-500 text-white rounded-xl font-black text-xs">{processing ? '...' : 'Void Item'}</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const SidebarIcon = ({ icon, label, active = false, onClick, count = 0 }) => (
        <div
            onClick={onClick}
            className={`relative flex flex-col items-center justify-center w-full py-4 cursor-pointer transition-all duration-200 group ${active ? 'text-orange-500' : 'text-gray-500 hover:text-orange-400'}`}
        >
            {active && <div className="absolute left-0 top-1 bottom-1 w-1 bg-orange-500 rounded-r-full shadow-[1px_0_6px_rgba(249,115,22,0.4)]"></div>}
            <div className={`p-2.5 rounded-xl transition-all ${active ? 'bg-orange-500/10' : 'group-hover:bg-white/5'}`}>
                <i className={`bi ${icon} text-xl`}></i>
                {count > 0 && (
                    <span className="absolute -top-1 -right-1 bg-red-500 text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center animate-pulse border-2 border-[#063024]">
                        {count}
                    </span>
                )}
            </div>
        </div>
    );

    const OrderTypeCard = ({ icon, title, subtitle, onClick, color = "orange" }) => (
        <div
            onClick={onClick}
            className="w-full max-w-[340px] aspect-square bg-white rounded-[2.5rem] p-10 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 transform hover:-translate-y-3 hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] group border-2 border-transparent hover:border-orange-100"
        >
            <div className={'w-24 h-24 rounded-3xl flex items-center justify-center mb-8 transition-transform group-hover:scale-110 ' + (color === 'orange' ? 'bg-orange-50 text-orange-500' : 'bg-green-50 text-green-500')}>
                <i className={'bi ' + icon + ' text-5xl'}></i>
            </div>
            <h2 className="text-3xl font-black text-gray-900 tracking-tight">{title}</h2>
            <p className="text-gray-400 font-medium mt-2">{subtitle}</p>
            <div className={'mt-10 flex items-center gap-2 font-bold text-sm ' + (color === 'orange' ? 'text-orange-500' : 'text-green-500')}>
                {title === 'Dine In' ? 'Pilih Meja' : 'Langsung Pesan'} <i className="bi bi-arrow-right"></i>
            </div>
        </div>
    );

    const TableCard = ({ table, active, onClick, guestCount, isSource, isTarget, managementAction }) => {
        const statusConfig = {
            available: { bg: 'bg-green-50', border: 'border-green-100', text: 'text-green-600', label: 'Tersedia' },
            occupied: { bg: 'bg-gray-50', border: 'border-gray-200', text: 'text-gray-400', label: 'Terisi' },
            reserved: { bg: 'bg-orange-50', border: 'border-orange-100', text: 'text-orange-500', label: 'Reservasi' }
        };
        const config = statusConfig[table.status] || statusConfig.available;

        // Debugging capacity
        const capacity = table.capacity || table.seats || 0;
        
        // In management mode, selection rules are different
        let isClickable = false;
        if (managementAction) {
            if (managementAction === 'reset' || managementAction === 'takeaway') {
                isClickable = table.status === 'occupied';
            } else if (managementAction === 'move' || managementAction === 'merge') {
                isClickable = true; // Can select any table as source/target
            }
        } else {
            isClickable = table.status === 'available' && capacity >= guestCount;
        }

        const cardBorder = isSource ? 'border-red-500 bg-red-50 shadow-lg' : 
                         isTarget ? 'border-blue-500 bg-blue-50 shadow-lg' :
                         active ? 'border-orange-500 bg-orange-50 shadow-lg' : 
                         config.border + ' ' + config.bg;

        return (
            <div
                onClick={() => {
                    if (isClickable) {
                        onClick(table);
                    }
                }}
                className={'relative p-6 rounded-[2rem] border-2 transition-all duration-300 flex flex-col justify-between aspect-video ' + cardBorder + ' ' + (isClickable ? 'cursor-pointer hover:shadow-md' : 'opacity-60 cursor-not-allowed')}
            >
                <div className="flex justify-between items-start">
                    <div className={'w-12 h-12 rounded-2xl flex items-center justify-center ' + (active || isSource || isTarget ? 'bg-orange-500 text-white' : 'bg-white ' + config.text)}>
                        <i className="bi bi-grid-fill text-xl"></i>
                    </div>
                    {isSource && <span className="bg-red-500 text-white text-[8px] font-black px-2 py-1 rounded-lg uppercase">Sumber</span>}
                    {isTarget && <span className="bg-blue-500 text-white text-[8px] font-black px-2 py-1 rounded-lg uppercase">Tujuan</span>}
                    {active && !isSource && !isTarget && <i className="bi bi-check-circle-fill text-orange-500 text-xl"></i>}
                </div>
                <div>
                    <h3 className={'text-2xl font-black tracking-tight ' + (active || isSource || isTarget ? 'text-orange-600' : 'text-gray-900')}>{table.name}</h3>
                    <div className="flex items-center gap-2 mt-1">
                        <i className="bi bi-people-fill text-xs text-gray-400"></i>
                        <span className="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Maks. {capacity}</span>
                    </div>
                </div>
                <div className={'mt-4 inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest ' + config.text}>
                    <div className={'w-1.5 h-1.5 rounded-full ' + (active || isSource || isTarget ? 'bg-orange-500' : config.text.replace('text-', 'bg-'))}></div>
                    {config.label}
                </div>
            </div>
        );
    };

    // --- Main Views ---

    const OrderTypeView = ({ onSelect }) => (
        <div className="w-full h-full flex flex-col items-center justify-center bg-[#daaa64] animate-in fade-in zoom-in duration-500">
            <h1 className="text-4xl md:text-5xl font-black text-gray-900 tracking-tighter mb-2 text-center px-4">Selamat Datang!</h1>
            <p className="text-lg md:text-xl text-black font-medium mb-10 md:mb-16 tracking-tight text-center px-4">Pilih jenis pesanan Anda untuk memulai</p>
            <div className="flex flex-col md:flex-row gap-6 md:gap-10 w-full max-w-4xl px-6">
                <OrderTypeCard
                    icon="bi-shop"
                    title="Dine In"
                    subtitle="Makan di tempat"
                    onClick={() => onSelect('DINE_IN')}
                    color="orange"
                />
                <OrderTypeCard
                    icon="bi-bag-heart-fill"
                    title="Take Away"
                    subtitle="Dibawa pulang"
                    onClick={() => onSelect('TAKE_AWAY')}
                    color="green"
                />
            </div>
        </div>
    );

    const TableSelectionView = ({ 
        tables, activeOrders, guestCount, setGuestCount, selectedTables, onSelect, onBack, onContinue, onOpenManagement,
        managementAction, sourceTable, targetTable, onSourceSelect, onTargetSelect, onConfirmManagement, onCancelManagement
    }) => {
        const handleTableClick = (table) => {
            if (managementAction) {
                if (managementAction === 'reset' || managementAction === 'takeaway') {
                    onSourceSelect(table);
                } else if (managementAction === 'move' || managementAction === 'merge') {
                    if (!sourceTable) onSourceSelect(table);
                    else if (sourceTable.id === table.id) onSourceSelect(null);
                    else onTargetSelect(table);
                }
            } else {
                onSelect(table);
            }
        };

        const totalSelectedCapacity = selectedTables.reduce((sum, t) => sum + (t.capacity || t.seats || 0), 0);
        const canContinue = totalSelectedCapacity >= guestCount || selectedTables.length > 0;

        const actionLabels = {
            merge: 'Gabung Meja',
            split: 'Pisah Meja',
            move: 'Pindah Meja',
            takeaway: 'Tanpa Meja',
            reset: 'Reset Meja'
        };

        return (
            <div className="w-full h-full flex bg-gray-50 animate-in slide-in-from-right duration-500">
                {/* Left: Guest Control */}
                <div className="w-[280px] bg-white border-r border-gray-100 p-6 flex flex-col">
                    <button onClick={onBack} className="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-xs mb-8 transition-colors">
                        <i className="bi bi-arrow-left"></i> Kembali
                    </button>

                    <div className="flex items-center gap-3 mb-2">
                        <div className="w-7 h-7 rounded-full bg-orange-500 text-white flex items-center justify-center font-black text-[10px]">2</div>
                        <h2 className="text-xl font-black text-gray-900 tracking-tight">Pilih Meja</h2>
                    </div>

                    <div className="mt-6">
                        <label className="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 block">Jumlah Tamu</label>
                        <div className="flex items-center justify-between bg-gray-50 rounded-2xl p-3 border border-gray-100 mb-5">
                            <button onClick={() => setGuestCount(Math.max(1, guestCount - 1))} className="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-gray-900 hover:bg-orange-500 hover:text-white transition-all active:scale-90">
                                <i className="bi bi-dash-lg text-lg"></i>
                            </button>
                            <span className="text-4xl font-black text-gray-900 w-16 text-center tracking-tighter">{guestCount}</span>
                            <button onClick={() => setGuestCount(guestCount + 1)} className="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-gray-900 hover:bg-orange-500 hover:text-white transition-all active:scale-90">
                                <i className="bi bi-plus-lg text-lg"></i>
                            </button>
                        </div>

                        <div className="grid grid-cols-4 gap-1.5 mb-8">
                            {[1, 2, 3, 4, 5, 6, 8, 10].map(n => (
                                <button
                                    key={n}
                                    onClick={() => setGuestCount(n)}
                                    className={`py-2.5 rounded-xl font-black text-xs transition-all ${guestCount === n ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'}`}
                                >
                                    {n}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="mt-auto space-y-2.5 pt-5 border-t border-gray-50">
                        <button 
                            onClick={onOpenManagement}
                            className="w-full py-3.5 mb-4 bg-gray-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest active:scale-95 transition-all shadow-lg shadow-gray-900/20 flex items-center justify-center gap-2"
                        >
                            <i className="bi bi-gear-fill"></i> Meja Settings
                        </button>
                        <div className="flex items-center gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                            <div className="w-2.5 h-2.5 rounded-full bg-green-500"></div> Tersedia
                        </div>
                        <div className="flex items-center gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                            <div className="w-2.5 h-2.5 rounded-full bg-gray-300"></div> Terisi
                        </div>
                        <div className="flex items-center gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                            <div className="w-2.5 h-2.5 rounded-full bg-orange-500"></div> Reservasi
                        </div>
                    </div>
                </div>

                {/* Right: Table Grid */}
                <div className="flex-1 p-6 flex flex-col overflow-hidden relative">
                    {managementAction && (
                        <div className="absolute top-0 inset-x-0 bg-gray-900 text-white p-4 z-10 flex justify-between items-center animate-in slide-in-from-top duration-300">
                            <div className="flex items-center gap-4">
                                <div className="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center">
                                    <i className="bi bi-gear-wide-connected text-lg"></i>
                                </div>
                                <div>
                                    <h4 className="text-base font-black tracking-tight">Mode: {actionLabels[managementAction]}</h4>
                                    <p className="text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                                        {managementAction === 'move' || managementAction === 'merge' 
                                            ? (!sourceTable ? 'Pilih meja sumber' : !targetTable ? 'Pilih meja tujuan' : 'Siap dikonfirmasi')
                                            : 'Pilih meja target'}
                                    </p>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={onCancelManagement} className="px-5 py-2.5 bg-white/10 hover:bg-white/20 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">Batal</button>
                                <button 
                                    onClick={onConfirmManagement} 
                                    disabled={!sourceTable || ((managementAction === 'move' || managementAction === 'merge') && !targetTable)}
                                    className="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 disabled:opacity-30 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all shadow-lg shadow-orange-500/20"
                                >
                                    Konfirmasi
                                </button>
                            </div>
                        </div>
                    )}

                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h3 className="text-lg font-black text-gray-900 tracking-tight">Meja tersedia untuk {guestCount}+ tamu</h3>
                            {selectedTables.length > 0 && (
                                <p className="text-[10px] font-bold text-orange-500 uppercase tracking-widest mt-0.5">
                                    Kapasitas: {totalSelectedCapacity} ({selectedTables.length} meja)
                                </p>
                            )}
                        </div>
                        <div className="bg-white px-3 py-1.5 rounded-full border border-gray-100 text-[9px] font-black text-gray-500 uppercase tracking-widest">
                            {tables.filter(t => t.status === 'available').length} tersedia
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto pr-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 content-start custom-scrollbar">
                        {tables.map(table => (
                            <TableCard
                                key={table.id}
                                table={table}
                                active={selectedTables.some(t => t.id === table.id)}
                                guestCount={guestCount}
                                onClick={handleTableClick}
                                managementAction={managementAction}
                                isSource={sourceTable?.id === table.id}
                                isTarget={targetTable?.id === table.id}
                            />
                        ))}
                    </div>

                    <div className="mt-6 flex justify-end">
                        <button
                            disabled={!canContinue || !!managementAction}
                            onClick={onContinue}
                            className="px-10 py-4 bg-gradient-to-r from-orange-500 to-yellow-400 text-white rounded-[2rem] font-black text-base shadow-xl shadow-orange-500/20 disabled:opacity-30 disabled:shadow-none transition-all active:scale-95"
                        >
                            Lanjut ke Menu <i className="bi bi-arrow-right ml-1.5"></i>
                        </button>
                    </div>
                </div>
            </div>
        );
    };

    const MenuView = ({ menuItems, categories, orderType, selectedTables, guestCount, onBack, onShowToast }) => {
        const [activeCategory, setActiveCategory] = useState('Makanan');
        const [cart, setCart] = useState([]);
        const [searchQuery, setSearchQuery] = useState('');
        const [customerName, setCustomerName] = useState('');
        const [customerCategory, setCustomerCategory] = useState('Umum');
        const [submitting, setSubmitting] = useState(false);
        const [isCartOpen, setIsCartOpen] = useState(true);

        // Map existing categories to 4 main categories
        const mainCategories = ['Makanan', 'Minuman', 'Snack', 'Lain-lain'];

        const filteredMenu = useMemo(() => {
            return (menuItems || []).filter(item => {
                // Normalize category: trim and compare case-insensitively,
                // fall back to 'Lain-lain' when no match found.
                const rawCat = (item.category || '').toString().trim();
                const matched = mainCategories.find(mc => mc.toLowerCase() === rawCat.toLowerCase());
                const itemCat = matched || 'Lain-lain';

                const matchCat = activeCategory === itemCat;
                const matchSearch = (item.name || '').toString().toLowerCase().includes(searchQuery.toLowerCase());
                return matchCat && matchSearch;
            });
        }, [menuItems, activeCategory, searchQuery]);

        const addToCart = (item) => {
            setCart(prev => {
                const existing = prev.find(i => i.id === item.id);
                if (existing) return prev.map(i => i.id === item.id ? {...i, qty: i.qty + 1} : i);
                return [...prev, {...item, qty: 1}];
            });
            setIsCartOpen(true); // Auto-open cart when item is added
        };

        const updateQty = (id, delta) => {
            setCart(prev => prev.map(i => {
                if (i.id === id) return {...i, qty: Math.max(0, i.qty + delta)};
                return i;
            }).filter(i => i.qty > 0));
        };

        const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);

        const handleSendToKitchen = async () => {
            if (cart.length === 0) return;
            if (orderType === 'DINE_IN' && !customerName.trim()) {
                onShowToast('Nama Pelanggan wajib diisi', 'error');
                return;
            }

            setSubmitting(true);
            try {
                // Prepare table data
                const primaryTable = selectedTables[0];
                const mergedTableIds = selectedTables.slice(1).map(t => t.id);

                const res = await fetch(api('/terminal/orders'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        table_id: primaryTable ? primaryTable.id : 1,
                        merged_table_ids: mergedTableIds.length > 0 ? JSON.stringify(mergedTableIds) : null,
                        customer_name: customerName,
                        guest_category: customerCategory,
                        order_type: orderType,
                        items: cart.map(i => ({
                            menu_item_id: i.id,
                            qty: i.qty,
                            note: ''
                        }))
                    })
                });

                const data = await res.json();
                if (data.id) {
                    onShowToast('Pesanan berhasil dikirim ke Kitchen!');
                    onBack();
                } else {
                    onShowToast(data.error || 'Gagal memproses pesanan', 'error');
                }
            } catch (e) {
                console.error(e);
                onShowToast('Terjadi kesalahan sistem.', 'error');
            } finally {
                setSubmitting(false);
            }
        };

        return (
            <div className="w-full h-full flex bg-gray-50 animate-in slide-in-from-right duration-500">
                {/* Main: Menu Area */}
                <div className="flex-1 flex flex-col overflow-hidden p-6 transition-all duration-500">
                    <div className="flex items-center justify-between gap-4 mb-6">
                        <div className="flex items-center gap-4">
                            <button onClick={onBack} className="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-xs transition-colors">
                                <i className="bi bi-arrow-left"></i> Kembali
                            </button>
                            <div className="flex gap-2">
                                <div className="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-2">
                                    <i className="bi bi-check-circle-fill"></i> Order: {orderType === 'DINE_IN' ? 'Dine In' : 'Take Away'}
                                </div>
                                {selectedTables.length > 0 && (
                                    <div className="bg-orange-100 text-orange-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-2">
                                        <i className="bi bi-check-circle-fill"></i> Meja: {selectedTables.map(t => t.name).join(', ')}
                                    </div>
                                )}
                            </div>
                        </div>
                        <button onClick={() => setIsCartOpen(!isCartOpen)} className="w-12 h-12 flex items-center justify-center bg-white rounded-xl shadow-sm text-gray-400 hover:bg-orange-500 hover:text-white transition-all">
                            <i className={`bi ${isCartOpen ? 'bi-chevron-right' : 'bi-chevron-left'} text-lg`}></i>
                        </button>
                    </div>

                    {/* Category Tabs & Search - Vertical Space Optimized */}
                    <div className="flex justify-between items-center mb-5 gap-3">
                        <div className="flex gap-1.5 overflow-x-auto pb-2 no-scrollbar flex-1 snap-x">
                            {mainCategories.map(cat => (
                                <button
                                    key={cat}
                                    onClick={() => setActiveCategory(cat)}
                                    className={`px-5 py-2 rounded-xl font-bold text-[10px] transition-all whitespace-nowrap snap-start ${activeCategory === cat ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'bg-white text-gray-500 hover:bg-gray-50'}`}
                                >
                                    {cat}
                                </button>
                            ))}
                        </div>
                        <div className="relative w-56">
                            <i className="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                type="text"
                                placeholder="Cari menu..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full bg-white border-none rounded-xl py-2.5 pl-10 pr-4 text-sm font-medium shadow-sm focus:ring-2 focus:ring-orange-500/20 transition-all"
                            />
                        </div>
                    </div>

                    {/* Dynamic Menu Grid */}
                    <div className={`flex-1 overflow-y-auto pr-2 grid gap-4 content-start custom-scrollbar transition-all duration-500 ${isCartOpen ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4' : 'grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6'}`}>
                        {filteredMenu.length === 0 ? (
                            <div className="col-span-full h-full flex flex-col items-center justify-center opacity-20 py-10">
                                <i className="bi bi-egg-fried text-5xl mb-2"></i>
                                <p className="font-bold text-xs uppercase tracking-widest">Tidak ada menu</p>
                            </div>
                        ) : (
                            filteredMenu.map(item => (
                                <div
                                    key={item.id}
                                    onClick={() => addToCart(item)}
                                    className="bg-white rounded-xl flex flex-col cursor-pointer transition-all duration-300 active:scale-95 group border border-transparent hover:border-orange-200 fluid-card-padding shadow-sm"
                                >
                                    <div className="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-50">
                                        <img src={item.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=200&h=200&auto=format&fit=crop'} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                                    </div>
                                    <h4 className="font-bold text-gray-800 leading-tight mb-1 min-h-[2rem] text-[13px]">{item.name}</h4>
                                    <div className="mt-auto flex justify-between items-center">
                                        <span className="text-orange-500 font-black text-sm">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</span>
                                        <div className="w-7 h-7 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition-all">
                                            <i className="bi bi-plus text-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                {/* Right: Compact Cart Area */}
                <div className={`bg-white border-l border-gray-100 flex flex-col shadow-[-4px_0_20px_rgba(0,0,0,0.02)] transition-all duration-500 ease-in-out ${isCartOpen ? 'w-[360px] p-6' : 'w-0 p-0'} overflow-hidden`}>
                    <div className="flex items-center justify-between mb-6 flex-shrink-0">
                        <div className="flex items-center gap-2">
                            <i className="bi bi-cart-fill text-xl text-orange-500"></i>
                            <h2 className="text-xl font-black text-gray-900 tracking-tight whitespace-nowrap">Pesanan Baru</h2>
                        </div>
                    </div>

                    {/* Compact Horizontal Customer Info Section */}
                    <div className="flex gap-3 mb-6 flex-shrink-0">
                        <div className="flex-1">
                            <label className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">Nama</label>
                            <input
                                type="text"
                                placeholder="..."
                                value={customerName}
                                onChange={(e) => setCustomerName(e.target.value)}
                                className="w-full bg-gray-50 border-none rounded-xl py-2 px-3 text-xs font-bold text-gray-900 focus:ring-1 focus:ring-orange-500 transition-all"
                            />
                        </div>
                        <div className="w-32">
                            <label className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">Kategori</label>
                            <select
                                value={customerCategory}
                                onChange={(e) => setCustomerCategory(e.target.value)}
                                className="w-full bg-gray-50 border-none rounded-xl py-2 px-3 text-xs font-bold text-gray-900 focus:ring-1 focus:ring-orange-500 transition-all appearance-none cursor-pointer"
                            >
                                {['Regular', 'Reservation', 'Majar Priority', 'Majar Signature'].map(cat => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto pr-1 custom-scrollbar space-y-3 min-h-0">
                        {cart.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center opacity-20">
                                <i className="bi bi-cart-x text-4xl mb-2"></i>
                                <p className="font-bold text-[10px] uppercase tracking-widest">Keranjang Kosong</p>
                            </div>
                        ) : (
                            cart.map(item => (
                                <div key={item.id} className="bg-gray-50/50 rounded-xl p-3 flex gap-3 animate-in slide-in-from-bottom duration-300 border border-gray-100/50">
                                    <img src={item.image_url} className="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                                    <div className="flex-1 min-w-0">
                                        <h5 className="font-bold text-gray-900 text-[11px] leading-tight truncate">{item.name}</h5>
                                        <p className="text-orange-500 font-bold text-[10px] mt-0.5">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</p>
                                        <div className="flex items-center gap-2 mt-2">
                                            <button onClick={() => updateQty(item.id, -1)} className="w-11 h-11 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-900 hover:bg-red-50 hover:text-red-500 transition-all"><i className="bi bi-dash text-lg"></i></button>
                                            <span className="font-black text-gray-900 text-sm w-6 text-center">{item.qty}</span>
                                            <button onClick={() => updateQty(item.id, 1)} className="w-11 h-11 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-900 hover:bg-green-50 hover:text-green-500 transition-all"><i className="bi bi-plus text-lg"></i></button>
                                        </div>
                                    </div>
                                    <div className="text-right font-black text-gray-900 text-[11px] self-start pt-1">
                                        Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Slim Sticky Footer */}
                    <div className="mt-auto pt-6 border-t border-dashed border-gray-200 space-y-3 flex-shrink-0">
                        <div className="flex justify-between items-center text-gray-400 font-bold text-[10px]">
                            <span className="uppercase tracking-widest">Subtotal</span>
                            <span>Rp {new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                        </div>
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-black text-gray-900 uppercase tracking-tighter">Total</span>
                            <span className="text-2xl font-black text-orange-500 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                        </div>
                        <button
                            disabled={cart.length === 0 || submitting}
                            onClick={handleSendToKitchen}
                            className="w-full py-3.5 bg-[#063024] text-white rounded-xl font-black text-sm uppercase tracking-widest shadow-lg shadow-[#063024]/10 transition-all active:scale-95 disabled:opacity-30 disabled:shadow-none"
                        >
                            {submitting ? 'Mengirim...' : 'Kirim Kitchen'}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    // --- Main Terminal App ---

    const KasirTerminal = () => {
        const [view, setView] = useState('ORDER_TYPE'); // ORDER_TYPE, TABLE_SELECT, MENU
        const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
        const [showTableManagement, setShowTableManagement] = useState(false);
        const [tableManagementAction, setTableManagementAction] = useState(null); // 'merge', 'split', 'move', 'takeaway', 'reset'
        const [sourceTable, setSourceTable] = useState(null);
        const [targetTable, setTargetTable] = useState(null);
        const [orderType, setOrderType] = useState('DINE_IN');
        const [guestCount, setGuestCount] = useState(2);
        const [selectedTables, setSelectedTables] = useState([]);
        const [tables, setTables] = useState(@json($tables));
        const [loadingTables, setLoadingTables] = useState(false);
        const [activeOrders, setActiveOrders] = useState([]);
        const [selectedOrder, setSelectedOrder] = useState(null);
        const [showOrderModal, setShowOrderModal] = useState(false);
        const [showPaymentModal, setShowPaymentModal] = useState(false);
        const [showVoidModal, setShowVoidModal] = useState(false);
        const [showVoidItemModal, setShowVoidItemModal] = useState(false);
        const [voidItemTarget, setVoidItemTarget] = useState(null);
        const [toast, setToast] = useState(null);
        const [confirmAction, setConfirmAction] = useState(null); // { title, message, onConfirm }

        const fetchTables = useCallback(async () => {
            try {
                const res = await fetch(api('/terminal/tables'));
                const data = await res.json();
                setTables(data || []);
            } catch (e) { console.error(e); }
        }, []);

        const fetchActiveOrders = useCallback(async () => {
            try {
                const res = await fetch(api('/terminal/orders?role=kasir'));
                const data = await res.json();
                setActiveOrders(data || []);

                if (selectedOrder) {
                    const updated = data.find(o => o.id === selectedOrder.id);
                    if (updated) setSelectedOrder(updated);
                    else setSelectedOrder(null);
                }
            } catch (e) { console.error(e); }
        }, [selectedOrder]);

        useEffect(() => {
            fetchTables();
            fetchActiveOrders();
            const interval = setInterval(() => {
                fetchTables();
                fetchActiveOrders();
            }, 5000);
            return () => clearInterval(interval);
        }, [fetchTables, fetchActiveOrders]);

        const handleVoidItem = async (orderId, itemId) => {
            setConfirmAction({
                title: 'VOID Item?',
                message: 'Apakah Anda yakin ingin membatalkan (VOID) item ini secara real-time?',
                type: 'danger',
                onConfirm: async () => {
                    try {
                        const res = await fetch(api(`/terminal/orders/${orderId}/items/${itemId}/void`), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            onShowToast('Item berhasil di-VOID');
                            fetchActiveOrders();
                        } else {
                            onShowToast(data.error || 'Gagal melakukan VOID', 'error');
                        }
                    } catch (e) {
                        onShowToast('Terjadi kesalahan sistem', 'error');
                    } finally {
                        setConfirmAction(null);
                    }
                }
            });
        };

        const [showReceiptPreview, setShowReceiptPreview] = useState(false);
        const [paidOrderData, setPaidOrderData] = useState(null);

        const handleFinalizePayment = async (method, amount, discount = 0, discountCode = '', discountId = '') => {
            try {
                const res = await fetch(api(`/terminal/orders/${selectedOrder.id}/finalize-payment`), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        payment_method: method.toLowerCase(),
                        amount_paid: amount,
                        discount_amount: discount,
                        discount_id: discountId
                    })
                });

                const data = await res.json();
                if (data.success) {
                    onShowToast(method === 'INVOICE' ? 'Pesanan dipindahkan ke Invoice!' : 'Pembayaran Berhasil! Meja tersedia.');
                    setShowPaymentModal(false);
                    
                    const paidOrder = data.order || selectedOrder;
                    
                    if (method.toLowerCase() !== 'invoice') {
                        setPaidOrderData({
                            ...paidOrder,
                            discount_amount: discount,
                            total: selectedOrder.total - discount
                        });
                        setShowReceiptPreview(true);
                    } else {
                        setSelectedOrder(null);
                    }

                    fetchActiveOrders();
                    fetchTables();
                } else {
                    onShowToast(data.error || 'Gagal memproses pembayaran', 'error');
                }
            } catch (e) {
                onShowToast('Terjadi kesalahan sistem.', 'error');
            }
        };

        const handlePrintReceipt = () => {
            if (!paidOrderData) return;
            const code = paidOrderData.code || paidOrderData.id;
            const url = api(`/order-receipt-print?code=${encodeURIComponent(code)}`);
            window.open(url, '_blank');
        };

        const handleShareReceipt = () => {
            onShowToast('Fitur WhatsApp Receipt segera hadir!', 'info');
        };

        const handleVoidOrder = async (orderId, reason, pin) => {
            try {
                const res = await fetch(api(`/terminal/orders/${orderId}/void`), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ reason, pin })
                });

                const data = await res.json();
                if (data.success) {
                    onShowToast('Pesanan berhasil di-VOID');
                    setShowVoidModal(false);
                    setSelectedOrder(null);
                    fetchActiveOrders();
                    fetchTables();
                } else {
                    onShowToast(data.error || 'Gagal melakukan VOID', 'error');
                }
            } catch (e) {
                console.error(e);
                onShowToast('Terjadi kesalahan sistem', 'error');
            }
        };

        const handleVoidItemSubmit = async (orderId, itemId, qty, reason) => {
            try {
                const res = await fetch(api(`/terminal/orders/${orderId}/items/${itemId}/void`), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ qty, reason })
                });

                const data = await res.json();
                if (data.success) {
                    onShowToast('Item berhasil di-VOID');
                    setShowVoidItemModal(false);
                    setVoidItemTarget(null);
                    // refresh
                    fetchActiveOrders();
                    fetchTables();
                    if (selectedOrder && selectedOrder.id === orderId) {
                        // reload selected order from server list
                        const updated = (await (await fetch(api(`/terminal/orders?role=kasir`))).json()).find(o => o.id === selectedOrder.id);
                        if (updated) setSelectedOrder(updated);
                        else setSelectedOrder(null);
                    }
                } else {
                    onShowToast(data.error || 'Gagal melakukan VOID', 'error');
                }
            } catch (e) {
                console.error(e);
                onShowToast('Terjadi kesalahan sistem', 'error');
            }
        };

        const onShowToast = (message, type = 'success') => {
            setToast({ message, type });
        };

        const handleSelectOrderType = (type) => {
            setOrderType(type);
            if (type === 'DINE_IN') {
                setView('TABLE_SELECT');
            } else {
                setSelectedTables([]);
                setView('MENU');
            }
        };

        const handleSelectTable = (table) => {
            setSelectedTables(prev => {
                const isSelected = prev.find(t => t.id === table.id);
                if (isSelected) return prev.filter(t => t.id !== table.id);
                return [...prev, table];
            });
        };

        const handleBack = () => {
            if (view === 'MENU' && orderType === 'TAKE_AWAY') setView('ORDER_TYPE');
            else if (view === 'MENU') setView('TABLE_SELECT');
            else if (view === 'TABLE_SELECT') setView('ORDER_TYPE');
            else if (view === 'ORDER_STATUS' || view === 'ORDER_HISTORY') setView('ORDER_TYPE');
        };

        const handleTableAction = async (actionId) => {
            setTableManagementAction(actionId);
            setShowTableManagement(false);
            setSourceTable(null);
            setTargetTable(null);

            if (actionId === 'reset') {
                onShowToast('Pilih meja yang akan di-RESET');
            } else if (actionId === 'move') {
                onShowToast('Pilih meja ASAL (yang terisi)');
            } else if (actionId === 'merge') {
                onShowToast('Pilih meja SUMBER (yang akan digabung)');
            } else if (actionId === 'takeaway') {
                onShowToast('Pilih meja yang akan di-TAKE AWAY');
            } else if (actionId === 'split') {
                onShowToast('Pilih meja yang pesanannya akan di-PISAH');
            }
        };

        const handleConfirmManagement = async () => {
            if (tableManagementAction === 'move' && sourceTable && targetTable) {
                const sourceOrder = activeOrders.find(o => o.table_id === sourceTable.id);
                if (!sourceOrder) return onShowToast('Tidak ada pesanan di meja asal', 'error');

                try {
                    const res = await fetch(api('/terminal/tables/move'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ order_id: sourceOrder.id, new_table_id: targetTable.id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        onShowToast('Meja berhasil dipindahkan');
                        resetManagement();
                        fetchTables();
                        fetchActiveOrders();
                    } else onShowToast(data.message || 'Gagal memindahkan meja', 'error');
                } catch (e) { onShowToast('Kesalahan sistem', 'error'); }

            } else if (tableManagementAction === 'merge' && sourceTable && targetTable) {
                const sourceOrder = activeOrders.find(o => o.table_id === sourceTable.id);
                const targetOrder = activeOrders.find(o => o.table_id === targetTable.id);
                
                if (sourceOrder && targetOrder) {
                    // Both have orders, merge orders
                    try {
                        const res = await fetch(api('/terminal/tables/merge'), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ target_order_id: targetOrder.id, source_order_ids: [sourceOrder.id] })
                        });
                        const data = await res.json();
                        if (data.success) {
                            onShowToast('Meja & Pesanan berhasil digabung');
                            resetManagement();
                            fetchTables();
                            fetchActiveOrders();
                        } else onShowToast(data.message || 'Gagal menggabungkan meja', 'error');
                    } catch (e) { onShowToast('Kesalahan sistem', 'error'); }
                } else {
                    // At least one is empty, or both empty. 
                    // We just treat them as "selected" for a new order.
                    const tablesToSelect = [sourceTable, targetTable];
                    setSelectedTables(tablesToSelect);
                    setView('MENU');
                    resetManagement();
                    onShowToast('Silakan lanjutkan pesanan untuk meja gabungan');
                }

            } else if (tableManagementAction === 'reset' && sourceTable) {
                setConfirmAction({
                    title: 'RESET MEJA?',
                    message: `Apakah Anda yakin ingin me-reset status meja ${sourceTable.name}? Ini akan mengosongkan status meja secara paksa.`,
                    onConfirm: async () => {
                        try {
                            const res = await fetch(api(`/terminal/tables/${sourceTable.id}/reset`), {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            const data = await res.json();
                            if (data.success) {
                                onShowToast('Meja berhasil di-reset');
                                resetManagement();
                                fetchTables();
                            } else onShowToast(data.error || 'Gagal me-reset meja', 'error');
                        } catch (e) { onShowToast('Kesalahan sistem', 'error'); }
                        finally { setConfirmAction(null); }
                    }
                });
            } else if (tableManagementAction === 'takeaway' && sourceTable) {
                const order = activeOrders.find(o => o.table_id === sourceTable.id);
                if (!order) return onShowToast('Tidak ada pesanan di meja tersebut', 'error');

                setConfirmAction({
                    title: 'PINDAH KE TAKE AWAY?',
                    message: `Apakah Anda yakin ingin mengubah pesanan di meja ${sourceTable.name} menjadi Tanpa Meja (Take Away)? Meja akan tersedia kembali.`,
                    onConfirm: async () => {
                        try {
                            const res = await fetch(api(`/terminal/orders/${order.id}/make-takeaway`), {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            const data = await res.json();
                            if (data.success) {
                                onShowToast('Pesanan diubah menjadi Take Away');
                                resetManagement();
                                fetchTables();
                                fetchActiveOrders();
                            } else onShowToast(data.error || 'Gagal mengubah tipe pesanan', 'error');
                        } catch (e) { onShowToast('Kesalahan sistem', 'error'); }
                        finally { setConfirmAction(null); }
                    }
                });
            }
        };

        const resetManagement = () => {
            setTableManagementAction(null);
            setSourceTable(null);
            setTargetTable(null);
        };

        const renderView = () => {
            switch (view) {
                case 'PENDING_APPROVAL':
                    return <PendingApprovalView activeOrders={activeOrders} onBack={() => setView('ORDER_TYPE')} onShowToast={onShowToast} fetchOrders={fetchActiveOrders} onVoidItem={(orderId, item) => { setVoidItemTarget({ orderId, item }); setShowVoidItemModal(true); }} />;
                case 'ORDER_STATUS':
                    return <OrderStatusView role="kasir" onBack={() => setView('ORDER_TYPE')} onShowToast={onShowToast} setConfirmAction={setConfirmAction} />;
                case 'ORDER_HISTORY':
                    return <OrderHistoryView onBack={() => setView('ORDER_TYPE')} />;
                case 'TABLE_SELECT':
                    return (
                        <TableSelectionView
                            tables={tables}
                            activeOrders={activeOrders}
                            guestCount={guestCount}
                            setGuestCount={setGuestCount}
                            selectedTables={selectedTables}
                            onSelect={handleSelectTable}
                            onBack={handleBack}
                            onContinue={() => setView('MENU')}
                            onOpenManagement={() => setShowTableManagement(true)}
                            managementAction={tableManagementAction}
                            sourceTable={sourceTable}
                            targetTable={targetTable}
                            onSourceSelect={setSourceTable}
                            onTargetSelect={setTargetTable}
                            onConfirmManagement={handleConfirmManagement}
                            onCancelManagement={resetManagement}
                        />
                    );
                case 'MENU':
                    return (
                        <MenuView
                            menuItems={ @json($menuItems) }
                            categories={ @json($categories) }
                            orderType={orderType}
                            selectedTables={selectedTables}
                            guestCount={guestCount}
                            onBack={() => setView('ORDER_TYPE')}
                            onShowToast={onShowToast}
                        />
                    );
                case 'ORDER_TYPE':
                default:
                    return <OrderTypeView onSelect={handleSelectOrderType} />;
            }
        };

        return (
            <div className="w-full h-full flex overflow-hidden bg-gray-50">
                {/* Ultra-Minimalist Icon-only Sidebar */}
                <div className="w-16 bg-[#063024] flex flex-col border-r border-white/5 shadow-2xl z-50 transition-all duration-300">
                    <div className="flex-1 flex flex-col py-6 overflow-y-auto no-scrollbar gap-2">
                        <SidebarIcon icon="bi-plus-circle" label="Pesan" active={view === 'ORDER_TYPE' || view === 'TABLE_SELECT' || view === 'MENU'} onClick={() => setView('ORDER_TYPE')} />
                        <SidebarIcon icon="bi-shield-lock" label="Approval" active={view === 'PENDING_APPROVAL'} onClick={() => setView('PENDING_APPROVAL')} count={activeOrders.filter(o => o.stage === 'WAITING_CASHIER').length} />
                        <SidebarIcon icon="bi-monitor" label="Monitor" active={view === 'MONITORING_PAGE'} onClick={() => setView('MONITORING_PAGE')} />
                        <SidebarIcon icon="bi-clock-history" label="History" active={view === 'ORDER_HISTORY'} onClick={() => setView('ORDER_HISTORY')} />
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1 h-full overflow-hidden relative">
                    {view === 'MONITORING_PAGE' ? (
                        <div className="p-10 h-full overflow-auto bg-gray-50">
                            <div className="flex items-center justify-between mb-10">
                                <div>
                                    <h2 className="text-3xl font-black text-gray-900 tracking-tight">Monitoring Orders</h2>
                                    <p className="text-gray-400 font-medium mt-1 uppercase text-[10px] tracking-widest">Pantau pesanan aktif secara real-time</p>
                                </div>
                                <div className="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 text-sm font-black text-gray-900 uppercase tracking-widest">
                                    Active: <span className="text-orange-500 ml-2">{activeOrders.filter(o => o.stage !== 'WAITING_CASHIER').length}</span>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                                {activeOrders.filter(o => o.stage !== 'WAITING_CASHIER').length === 0 ? (
                                    <div className="col-span-full h-96 flex flex-col items-center justify-center opacity-20">
                                        <i className="bi bi-inbox text-8xl mb-4"></i>
                                        <p className="font-black uppercase tracking-[0.3em]">No active orders</p>
                                    </div>
                                ) : (
                                    activeOrders.filter(o => o.stage !== 'WAITING_CASHIER').map(order => (
                                        <div 
                                            key={order.id} 
                                            onClick={() => { setSelectedOrder(order); setShowOrderModal(true); }} 
                                            className={`p-6 bg-white rounded-[2rem] shadow-sm active:scale-95 transition-all border-2 ${selectedOrder?.id === order.id ? 'border-orange-500 shadow-xl' : 'border-transparent shadow-md'}`}
                                        >
                                            <div className="flex justify-between items-start mb-4">
                                                <div>
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <h4 className="text-xl font-black text-gray-900 tracking-tight">Meja {order.table?.name || 'TA'}</h4>
                                                        <span className="text-[8px] font-black bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded-md uppercase">{order.guest_category || 'Umum'}</span>
                                                    </div>
                                                    <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">{order.customer_name || 'Guest'}</p>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-lg font-black text-orange-500 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(order.total)}</div>
                                                    <span className={`inline-block mt-1 text-[7px] font-black px-1.5 py-0.5 rounded-md uppercase tracking-widest ${
                                                        order.stage === 'COOKING' ? 'bg-blue-50 text-blue-500' : 
                                                        order.stage === 'READY' ? 'bg-green-50 text-green-500' : 
                                                        'bg-gray-100 text-gray-400'
                                                    }`}>
                                                        {order.stage.replace(/_/g,' ')}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="space-y-1.5 border-t border-gray-50 pt-4">
                                                {order.items.map((i, idx) => (
                                                    <div key={idx} className="flex justify-between text-xs">
                                                        <span className="text-gray-500 font-medium truncate pr-4">{i.qty}x {i.menu_name}</span>
                                                        <span className={`text-[7px] font-black uppercase flex-shrink-0 ${i.status === 'ready' ? 'text-green-500' : 'text-gray-300'}`}>{i.status}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    ) : (
                        renderView()
                    )}
                </div>

                {/* Modals & Toasts */}
                {showPaymentModal && selectedOrder && (
                    <PaymentModal
                        order={selectedOrder}
                        onConfirm={handleFinalizePayment}
                        onClose={() => setShowPaymentModal(false)}
                        onShowToast={onShowToast}
                    />
                )}
                {showOrderModal && selectedOrder && (
                    <div className="fixed inset-0 z-[9000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                        <div className="bg-white w-full max-w-4xl rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                            <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <div>
                                    <h3 className="text-xl font-black text-gray-900 tracking-tight">Detail Pesanan</h3>
                                    <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Order ID: #{selectedOrder.code || selectedOrder.id}</p>
                                </div>
                                <button onClick={() => { setShowOrderModal(false); setSelectedOrder(null); }} className="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-gray-400 active:text-gray-900 active:scale-95 transition-all"><i className="bi bi-x-lg text-xl"></i></button>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-0">
                                <div className="p-6 border-r border-gray-100">
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="text-sm font-black text-gray-900">Items</div>
                                        <div className="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Swipe left to Void</div>
                                    </div>
                                    <div className="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-2 no-scrollbar">
                                        {selectedOrder.items.map((item, idx) => (
                                            <SwipeableItem 
                                                key={idx} 
                                                onSwipe={() => { setVoidItemTarget({ orderId: selectedOrder.id, item }); setShowVoidItemModal(true); }}
                                                threshold={80}
                                            >
                                                <div className="flex-1 flex justify-between items-center p-3 bg-white border border-gray-50 rounded-xl">
                                                    <div>
                                                        <div className="text-sm font-bold text-gray-900 leading-tight">{item.qty}x {item.menu_name}</div>
                                                        <div className="text-[10px] font-bold text-gray-400 mt-0.5 uppercase tracking-widest">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</div>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="text-sm font-black text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</div>
                                                        <span className={`text-[7px] font-black uppercase px-1.5 py-0.5 rounded-md mt-1 inline-block ${
                                                            item.status === 'ready' ? 'bg-green-50 text-green-500' : 
                                                            item.status === 'cooking' ? 'bg-blue-50 text-blue-500' : 
                                                            'bg-gray-50 text-gray-400'
                                                        }`}>{item.status}</span>
                                                    </div>
                                                </div>
                                            </SwipeableItem>
                                        ))}
                                    </div>
                                </div>
                                
                                <div className="p-6 bg-gray-50/30 flex flex-col justify-between">
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="p-4 bg-white rounded-2xl shadow-sm border border-gray-100">
                                                <div className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Meja</div>
                                                <div className="text-xl font-black text-gray-900 tracking-tight">{selectedOrder.table?.name || 'TA'}</div>
                                            </div>
                                            <div className="p-4 bg-white rounded-2xl shadow-sm border border-gray-100">
                                                <div className="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Customer</div>
                                                <div className="text-sm font-black text-gray-900 tracking-tight truncate">{selectedOrder.customer_name || 'Guest'}</div>
                                            </div>
                                        </div>
                                        
                                        <div className="p-6 bg-gray-900 rounded-[2rem] shadow-xl">
                                            <div className="text-[9px] font-black text-orange-500 uppercase tracking-[0.2em] mb-2">Total Tagihan</div>
                                            <div className="text-3xl font-black text-white tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(selectedOrder.total)}</div>
                                        </div>
                                    </div>
                                    
                                    <div className="flex gap-3 mt-8">
                                        <button onClick={() => { setShowVoidModal(true); setShowOrderModal(false); }} className="flex-1 py-4 bg-red-50 text-red-500 rounded-2xl font-black text-sm uppercase tracking-widest active:scale-95 transition-all">Void Order</button>
                                        <button onClick={() => { setShowPaymentModal(true); setShowOrderModal(false); }} className="flex-[2] py-4 bg-orange-500 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-orange-500/30 active:scale-95 transition-all">Bayar Sekarang</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
                {showVoidModal && selectedOrder && (
                    <VoidOrderModal
                        order={selectedOrder}
                        onClose={() => setShowVoidModal(false)}
                        onVoid={(reason, pin) => handleVoidOrder(selectedOrder.id, reason, pin)}
                    />
                )}
                {showVoidItemModal && voidItemTarget && (
                    <VoidItemModal
                        target={voidItemTarget}
                        onClose={() => { setShowVoidItemModal(false); setVoidItemTarget(null); }}
                        onSubmit={handleVoidItemSubmit}
                    />
                )}
                {confirmAction && (
                    <ConfirmModal
                        {...confirmAction}
                        onClose={() => setConfirmAction(null)}
                    />
                )}
                {showReceiptPreview && paidOrderData && (
                    <ReceiptPreviewModal
                        order={paidOrderData}
                        onPrint={handlePrintReceipt}
                        onShare={handleShareReceipt}
                        onClose={() => {
                            setShowReceiptPreview(false);
                            setPaidOrderData(null);
                            setSelectedOrder(null);
                        }}
                    />
                )}
                {toast && <Toast {...toast} onClose={() => setToast(null)} />}
                
                {showTableManagement && (
                    <TableManagementModal
                        onClose={() => setShowTableManagement(false)}
                        onAction={handleTableAction}
                        tables={tables}
                        activeOrders={activeOrders}
                    />
                )}
            </div>
        );
    };

    // --- Order Status & History Components ---

    const PendingApprovalView = ({ activeOrders, onBack, onShowToast, fetchOrders, onVoidItem }) => {
        const pending = activeOrders.filter(o => o.stage === 'WAITING_CASHIER');
        const [processing, setProcessing] = useState(false);

        const handleApprove = async (orderId) => {
            setProcessing(true);
            try {
                const res = await fetch(api(`/terminal/orders/${orderId}/approve`), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    onShowToast('Pesanan disetujui & dikirim ke Dapur!');
                    fetchOrders();
                } else {
                    onShowToast(data.error || 'Gagal menyetujui pesanan', 'error');
                }
            } catch (e) {
                onShowToast('Kesalahan sistem', 'error');
            } finally {
                setProcessing(false);
            }
        };

        const renderItems = (order) => {
            return order.items.map((item, idx) => (
                <SwipeableItem 
                    key={idx} 
                    onSwipe={() => onVoidItem(order.id, item)}
                    threshold={80}
                >
                    <div className="flex-1 flex justify-between items-center p-4 border-b border-gray-50 last:border-none">
                        <div>
                            <span className="text-lg font-black text-gray-900 leading-tight">{item.qty}x {item.menu_name}</span>
                            <div className="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</div>
                        </div>
                        <span className="text-lg font-black text-gray-900 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</span>
                    </div>
                </SwipeableItem>
            ));
        };

        return (
            <div className="w-full h-full flex flex-col p-8 bg-gray-50 animate-in fade-in duration-500 overflow-hidden">
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <button onClick={onBack} className="w-12 h-12 rounded-2xl bg-white shadow-md flex items-center justify-center text-gray-400 active:text-gray-900 active:scale-95 transition-all"><i className="bi bi-arrow-left text-2xl"></i></button>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tighter">Pending Approval</h1>
                    </div>
                    <div className="bg-orange-500 text-white px-6 py-3 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-orange-500/20">
                        {pending.length} Pesanan
                    </div>
                </div>

                <div className="flex-1 overflow-x-auto pb-8 flex gap-8 items-start snap-x snap-mandatory no-scrollbar pr-20">
                    {pending.length === 0 ? (
                        <div className="flex-1 h-full flex flex-col items-center justify-center opacity-10">
                            <i className="bi bi-shield-check text-[10rem]"></i>
                            <p className="font-black uppercase tracking-[0.5em] mt-8 text-xl">Antrian Kosong</p>
                        </div>
                    ) : (
                        pending.map(order => (
                            <div key={order.id} className="min-w-[400px] bg-white rounded-[2.5rem] p-8 shadow-2xl border border-gray-100 flex flex-col h-fit snap-center transition-transform active:scale-[0.98]">
                                <div className="flex justify-between items-start mb-8">
                                    <div>
                                        <h4 className="font-black text-gray-900 text-3xl tracking-tight mb-1">Meja {order.table?.name || 'TA'}</h4>
                                        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em]">{order.customer_name} • {order.guest_category}</p>
                                    </div>
                                    <span className="text-[10px] font-black bg-orange-50 text-orange-500 px-3 py-1.5 rounded-xl tracking-widest">WAITING</span>
                                </div>

                                <div className="space-y-1 mb-8 border-y-2 border-dashed border-gray-100 py-6 max-h-[40vh] overflow-y-auto custom-scrollbar no-scrollbar">
                                    {renderItems(order)}
                                </div>

                                <div className="flex justify-between items-center mb-8 px-2">
                                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Tagihan</span>
                                    <span className="text-3xl font-black text-gray-900 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(order.total)}</span>
                                </div>

                                <button 
                                    disabled={processing}
                                    onClick={() => handleApprove(order.id)}
                                    className="w-full py-5 bg-orange-500 text-white rounded-[2rem] font-black text-xl uppercase tracking-widest active:scale-95 transition-all shadow-2xl shadow-orange-500/30"
                                >
                                    {processing ? 'Memproses...' : 'Setujui & Kirim'}
                                </button>
                                <div className="mt-4 flex items-center justify-center gap-2 text-gray-300">
                                    <i className="bi bi-chevron-double-left text-xs animate-pulse"></i>
                                    <span className="text-[9px] font-bold uppercase tracking-widest">Geser kiri pada item untuk Void</span>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        );
    };

    const OrderStatusView = ({ role, onBack, onShowToast, setConfirmAction }) => {
        const [orders, setOrders] = useState([]);
        const [loading, setLoading] = useState(true);

        const fetchOrders = useCallback(async () => {
            try {
                const res = await fetch(api(`/terminal/orders?role=${role}`));
                const data = await res.json();
                setOrders(data);
            } catch (e) { console.error(e); }
            finally { setLoading(false); }
        }, [role]);

        useEffect(() => {
            fetchOrders();
            const interval = setInterval(fetchOrders, 5000);
            return () => clearInterval(interval);
        }, [fetchOrders]);

        const handleApprove = async (id) => {
            setConfirmAction({
                title: 'Approve & Kirim ke Dapur?',
                message: 'Pesanan ini akan segera diproses oleh tim dapur.',
                onConfirm: async () => {
                    try {
                        const res = await fetch(api(`/terminal/orders/${id}/approve`), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        if (res.ok) {
                            onShowToast('Pesanan disetujui & dikirim ke Kitchen!');
                            fetchOrders();
                        } else {
                            onShowToast('Gagal menyetujui pesanan', 'error');
                        }
                    } catch (e) {
                        onShowToast('Terjadi kesalahan sistem', 'error');
                    } finally {
                        setConfirmAction(null);
                    }
                }
            });
        };

        const renderItems = (order) => {
            return order.items.map((item, idx) => {
                const statusColors = {
                    'pending': 'bg-gray-100 text-gray-400',
                    'cooking': 'bg-blue-100 text-blue-600',
                    'ready': 'bg-green-100 text-green-600',
                    'served': 'bg-purple-100 text-purple-600',
                    'void': 'bg-red-100 text-red-600'
                };
                return (
                    <div key={idx} className="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                        <div>
                            <p className="text-xs font-bold text-gray-900">{item.qty}x {item.menu_name}</p>
                            <span className={`text-[8px] font-black uppercase px-1.5 py-0.5 rounded ${statusColors[item.status] || 'bg-gray-100'}`}>
                                {item.status}
                            </span>
                        </div>
                    </div>
                );
            });
        };

        return (
            <div className="flex-1 h-full bg-gray-50 p-8 flex flex-col overflow-hidden animate-in fade-in duration-500">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h2 className="text-3xl font-black text-gray-900 tracking-tight">Antrian Pesanan</h2>
                        <p className="text-gray-400 font-bold text-xs uppercase tracking-widest mt-1">Monitoring & Approval POS</p>
                    </div>
                    <button onClick={onBack} className="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-gray-900 transition-all">
                        <i className="bi bi-x-lg"></i>
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto custom-scrollbar">
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 pb-8">
                        {/* Column: Pending Approval */}
                        <div className="space-y-4">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></div>
                                <h3 className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Approval ({orders.filter(o => o.stage === 'WAITING_CASHIER').length})</h3>
                            </div>
                            {orders.filter(o => o.stage === 'WAITING_CASHIER').map(order => (
                                <div key={order.id} className="bg-white rounded-[2rem] p-6 shadow-sm border border-orange-100 border-l-4 border-l-orange-500 animate-in slide-in-from-bottom duration-300">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 className="font-black text-gray-900">Meja {order.table?.name || 'TA'}</h4>
                                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{order.customer_name} • {order.guest_category}</p>
                                        </div>
                                        <span className="text-[10px] font-black bg-orange-50 text-orange-500 px-2 py-1 rounded-lg">WAITING</span>
                                    </div>
                                    <div className="space-y-1 mb-6 max-h-32 overflow-y-auto custom-scrollbar">
                                        {renderItems(order)}
                                    </div>
                                    <button
                                        onClick={() => handleApprove(order.id)}
                                        className="w-full py-3 bg-orange-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-500/20 active:scale-95 transition-all"
                                    >
                                        Approve & Kirim
                                    </button>
                                </div>
                            ))}
                        </div>

                        {/* Column: In Progress (Cooking/Ready) */}
                        <div className="space-y-4">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-2 h-2 rounded-full bg-blue-500"></div>
                                <h3 className="text-[10px] font-black text-gray-400 uppercase tracking-widest">In Progress ({orders.filter(o => ['COOKING', 'READY'].includes(o.stage)).length})</h3>
                            </div>
                            {orders.filter(o => ['COOKING', 'READY'].includes(o.stage)).map(order => (
                                <div key={order.id} className="bg-white rounded-[2rem] p-6 shadow-sm border border-blue-50 border-l-4 border-l-blue-500">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 className="font-black text-gray-900">Meja {order.table?.name || 'TA'}</h4>
                                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{order.customer_name}</p>
                                        </div>
                                        <span className={`text-[10px] font-black px-2 py-1 rounded-lg ${order.stage === 'READY' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'}`}>
                                            {order.stage}
                                        </span>
                                    </div>
                                    <div className="space-y-1 max-h-40 overflow-y-auto custom-scrollbar">
                                        {renderItems(order)}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Column: Served (Waiting for Payment) */}
                        <div className="space-y-4">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-2 h-2 rounded-full bg-purple-500"></div>
                                <h3 className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Served ({orders.filter(o => o.stage === 'SERVED').length})</h3>
                            </div>
                            {orders.filter(o => o.stage === 'SERVED').map(order => (
                                <div key={order.id} className="bg-white rounded-[2rem] p-6 shadow-sm border border-purple-50 border-l-4 border-l-purple-500">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 className="font-black text-gray-900">Meja {order.table?.name || 'TA'}</h4>
                                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{order.customer_name}</p>
                                        </div>
                                        <span className="text-[10px] font-black bg-purple-50 text-purple-600 px-2 py-1 rounded-lg">READY TO PAY</span>
                                    </div>
                                    <div className="space-y-1 mb-4 max-h-32 overflow-y-auto custom-scrollbar">
                                        {renderItems(order)}
                                    </div>
                                    <div className="text-lg font-black text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(order.total)}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const OrderHistoryView = ({ onBack }) => {
        const [history, setHistory] = useState([]);
        const [loading, setLoading] = useState(true);
        const [selectedOrder, setSelectedOrder] = useState(null);

        useEffect(() => {
            fetch(api('/terminal/orders/history'))
                .then(res => res.json())
                .then(data => {
                    setHistory(data);
                    setLoading(false);
                });
        }, []);

        return (
            <div className="w-full h-full flex bg-gray-50 animate-in fade-in duration-500 overflow-hidden relative">
                {/* Left Side: Transactions List (Compact Table) */}
                <div className="flex-1 flex flex-col p-6">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-4">
                            <button onClick={onBack} className="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-gray-400 active:scale-95 transition-all"><i className="bi bi-arrow-left text-xl"></i></button>
                            <h1 className="text-2xl font-black text-gray-900 tracking-tighter">History Hari Ini</h1>
                        </div>
                        <div className="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white px-4 py-2 rounded-full border border-gray-100 shadow-sm">
                            Total: <span className="text-orange-500 ml-1">{history.length} Transaksi</span>
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto bg-white rounded-2xl border border-gray-100 shadow-sm custom-scrollbar">
                        <table className="w-full text-left border-collapse">
                            <thead className="sticky top-0 bg-white z-10">
                                <tr className="border-b border-gray-50">
                                    <th className="p-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Waktu</th>
                                    <th className="p-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Kode</th>
                                    <th className="p-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Meja</th>
                                    <th className="p-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">Total</th>
                                    <th className="p-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.map(order => (
                                    <tr 
                                        key={order.id} 
                                        onClick={() => setSelectedOrder(order)}
                                        className={'border-b border-gray-50 hover:bg-orange-50 cursor-pointer transition-colors group ' + (selectedOrder?.id === order.id ? 'bg-orange-50' : '')}
                                    >
                                        <td className="p-3 text-[11px] font-bold text-gray-600">
                                            {new Date(order.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}
                                        </td>
                                        <td className="p-3 text-[11px] font-black text-gray-900 group-hover:text-orange-600">{order.code}</td>
                                        <td className="p-3 text-[11px] font-bold text-gray-600">{order.table?.name || 'TA'}</td>
                                        <td className="p-3 text-[11px] font-black text-orange-500 text-right">Rp {new Intl.NumberFormat('id-ID').format(order.total)}</td>
                                        <td className="p-3">
                                            <span className={'px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest ' + (order.stage === 'DONE' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400')}>
                                                {order.stage}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Right Side: Detail Panel (Compact) */}
                <div className={'bg-white border-l border-gray-100 shadow-2xl transition-all duration-300 flex flex-col ' + (selectedOrder ? 'w-[400px]' : 'w-0 overflow-hidden')}>
                    {selectedOrder && (
                        <>
                            <div className="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <div>
                                    <h3 className="text-lg font-black text-gray-900 tracking-tight">Detail Transaksi</h3>
                                    <p className="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">{selectedOrder.code}</p>
                                </div>
                                <button onClick={() => setSelectedOrder(null)} className="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center text-gray-400 active:scale-95 transition-all"><i className="bi bi-x-lg text-lg"></i></button>
                            </div>

                            <div className="p-5 space-y-4 flex-1 overflow-y-auto custom-scrollbar">
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="p-3 bg-gray-50 rounded-xl">
                                        <p className="text-[7px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Waktu</p>
                                        <p className="text-[10px] font-black text-gray-900 leading-tight">
                                            {new Date(selectedOrder.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })} | {new Date(selectedOrder.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}
                                        </p>
                                    </div>
                                    <div className="p-3 bg-gray-50 rounded-xl">
                                        <p className="text-[7px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Kasir</p>
                                        <p className="text-[10px] font-black text-gray-900 leading-tight truncate">{selectedOrder.kasir?.name || '-'}</p>
                                    </div>
                                    <div className="p-3 bg-gray-50 rounded-xl">
                                        <p className="text-[7px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Customer</p>
                                        <p className="text-[10px] font-black text-gray-900 leading-tight truncate">{selectedOrder.customer_name || 'Guest'}</p>
                                    </div>
                                    <div className="p-3 bg-gray-50 rounded-xl">
                                        <p className="text-[7px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Metode</p>
                                        <p className="text-[10px] font-black text-orange-500 uppercase">{selectedOrder.payment_method || 'Tunai'}</p>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <p className="text-[9px] font-black text-gray-400 uppercase tracking-widest">Item Pesanan</p>
                                    <div className="space-y-1.5">
                                        {selectedOrder.items?.map((item, idx) => (
                                            <div key={idx} className="flex justify-between items-center p-2.5 border border-gray-50 rounded-xl bg-gray-50/30">
                                                <div className="flex-1 pr-2 min-w-0">
                                                    <p className="text-[11px] font-bold text-gray-900 leading-tight truncate">{item.qty}x {item.menu_name}</p>
                                                    <p className="text-[8px] text-gray-400 uppercase mt-0.5">@ {new Intl.NumberFormat('id-ID').format(item.price)}</p>
                                                </div>
                                                <p className="text-[11px] font-black text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="p-5 border-t border-dashed border-gray-200 bg-white">
                                <div className="flex justify-between items-center">
                                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Bayar</span>
                                    <span className="text-2xl font-black text-orange-500 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(selectedOrder.total)}</span>
                                </div>
                                <button onClick={() => setSelectedOrder(null)} className="w-full mt-4 py-3 bg-gray-900 text-white rounded-xl font-black text-[9px] uppercase tracking-widest active:scale-95 transition-all shadow-lg shadow-gray-900/10">Tutup Detail</button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        );
    };

    const TableManagementModal = ({ onClose, onAction, tables, activeOrders }) => {
        const actions = [
            { id: 'merge', label: 'Gabung Meja', icon: 'bi-intersect' },
            { id: 'split', label: 'Pisah Meja', icon: 'bi-exclude' },
            { id: 'move', label: 'Pindah Meja', icon: 'bi-arrow-right-square' },
            { id: 'takeaway', label: 'Tanpa Meja', icon: 'bi-bag-x' },
            { id: 'reset', label: 'Reset Meja', icon: 'bi-arrow-counterclockwise' }
        ];

        return (
            <div className="fixed inset-0 z-[10000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-md rounded-[3rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300 flex flex-col">
                    <div className="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 className="text-2xl font-black text-gray-900 tracking-tight">Manajemen Meja</h3>
                        <button onClick={onClose} className="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-gray-400 active:scale-95 transition-all"><i className="bi bi-x-lg text-xl"></i></button>
                    </div>
                    <div className="p-6 grid grid-cols-1 gap-3">
                        {actions.map(action => (
                            <button
                                key={action.id}
                                onClick={() => onAction(action.id)}
                                className="flex items-center gap-6 p-6 rounded-[2rem] bg-white border-2 border-gray-50 hover:border-orange-100 active:scale-[0.98] active:bg-orange-50 transition-all group"
                            >
                                <div className="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-orange-500 group-hover:text-white transition-all shadow-sm">
                                    <i className={'bi ' + action.icon + ' text-2xl'}></i>
                                </div>
                                <span className="text-xl font-black text-gray-700 group-hover:text-gray-900">{action.label}</span>
                            </button>
                        ))}
                    </div>
                </div>
            </div>
        );
    };

    const root = ReactDOM.createRoot(document.getElementById('kasir-root'));
    root.render(<KasirTerminal />);

</script>

    <style>
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #E5E7EB;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #D1D5DB;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
@endsection
