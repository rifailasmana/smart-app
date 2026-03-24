@extends('layouts.terminal')

@section('title', 'Kasir - Majar Signature')
@section('terminal_role', 'KASIR')

@section('header_extra')
    <div class="flex items-center gap-4 border-l border-gray-700 pl-4">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">System: Online</span>
        </div>
    </div>
@endsection

@section('content')
    <div class="w-full h-full" id="kasir-root"></div>
@endsection

@section('extra_js')
    <script type="text/babel">
    const { useState, useEffect, useMemo, useCallback } = React;

    // --- Components ---

    const Toast = ({ message, type = 'success', onClose }) => {
        useEffect(() => {
            const timer = setTimeout(onClose, 3000);
            return () => clearTimeout(timer);
        }, [onClose]);

        return (
            <div className={`fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] animate-in slide-in-from-bottom duration-300`}>
                <div className={`px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`}>
                    <i className={`bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}`}></i>
                    <span className="font-bold text-sm">{message}</span>
                </div>
            </div>
        );
    };

    const PaymentModal = ({ order, onConfirm, onClose }) => {
        const [method, setPaymentMethod] = useState('Tunai');
        const [discountType, setDiscountType] = useState('nominal'); // nominal or percent
        const [discountValue, setDiscountValue] = useState(0);
        const [couponCode, setCouponCode] = useState('');
        const [amountPaid, setAmountPaid] = useState(order.total);
        const [processing, setProcessing] = useState(false);
        const [showQR, setShowQR] = useState(false);

        const canUseCoupon = order.guest_category === 'Majar Owner' || order.guest_category === 'Member';

        const discountAmount = useMemo(() => {
            if (discountType === 'percent') return (order.total * (discountValue / 100));
            return discountValue;
        }, [order.total, discountType, discountValue]);

        const finalTotal = Math.max(0, order.total - discountAmount);

        useEffect(() => {
            if (method !== 'Tunai') setAmountPaid(finalTotal);
        }, [finalTotal, method]);

        const handleFinalize = async () => {
            if (method === 'QRIS' && !showQR) {
                setShowQR(true);
                return;
            }
            setProcessing(true);
            await onConfirm(method, amountPaid, discountAmount, couponCode);
            setProcessing(false);
        };

        if (showQR) {
            return (
                <div className="fixed inset-0 z-[9000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                    <div className="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                        <div className="p-8 border-b border-gray-100 flex justify-between items-center">
                            <h2 className="text-2xl font-black text-gray-900 tracking-tight">Scan QRIS</h2>
                            <button onClick={() => setShowQR(false)} className="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center hover:bg-gray-100 transition-colors">
                                <i className="bi bi-arrow-left"></i>
                            </button>
                        </div>
                        <div className="p-8 flex flex-col items-center">
                            <div className="bg-gray-50 rounded-3xl p-6 mb-6 w-full text-center">
                                <p className="text-gray-400 font-bold text-xs uppercase tracking-widest mb-2">Total Tagihan</p>
                                <p className="text-3xl font-black text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(finalTotal)}</p>
                            </div>

                            <div className="relative group mb-6">
                                <div className="absolute -inset-4 bg-gradient-to-tr from-orange-500 to-yellow-400 rounded-[2.5rem] blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                                <div className="relative bg-white p-6 rounded-[2rem] shadow-xl">
                                    <img
                                        src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=MAJAR-POS-${order.id}-${finalTotal}`}
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
            <div className="fixed inset-0 z-[9000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300 flex flex-col lg:flex-row">
                    {/* Left: Summary */}
                    <div className="w-full lg:w-[320px] bg-gray-50 p-8 border-r border-gray-100">
                        <div className="flex justify-between items-center mb-8">
                            <h2 className="text-xl font-black text-gray-900 tracking-tight">Ringkasan</h2>
                            <button onClick={onClose} className="lg:hidden text-gray-400"><i className="bi bi-x-lg"></i></button>
                        </div>

                        <div className="mb-6 flex items-center gap-3 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                            <div className="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                <i className="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <p className="text-[8px] font-black text-gray-400 uppercase tracking-widest">Kategori</p>
                                <p className="text-xs font-black text-gray-900">{order.guest_category || 'Reguler'}</p>
                            </div>
                        </div>

                        <div className="space-y-4 max-h-[300px] overflow-y-auto custom-scrollbar pr-2 mb-8">
                            {order.items.map((item, idx) => (
                                <div key={idx} className="flex justify-between text-xs">
                                    <span className="text-gray-500 font-medium">{item.qty}x {item.menu_name}</span>
                                    <span className="font-black text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</span>
                                </div>
                            ))}
                        </div>

                        <div className="pt-6 border-t-2 border-dashed border-gray-200 space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-400 font-bold uppercase tracking-widest text-[10px]">Subtotal</span>
                                <span className="font-black text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(order.total)}</span>
                            </div>
                            <div className="flex justify-between text-sm text-red-500">
                                <span className="font-bold uppercase tracking-widest text-[10px]">Diskon</span>
                                <span className="font-black">- Rp {new Intl.NumberFormat('id-ID').format(discountAmount)}</span>
                            </div>
                            <div className="flex justify-between items-center pt-4">
                                <span className="text-gray-900 font-black uppercase tracking-widest text-[10px]">Total Akhir</span>
                                <span className="text-2xl font-black text-orange-500 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(finalTotal)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Right: Controls */}
                    <div className="flex-1 p-10 bg-white">
                        <div className="hidden lg:flex justify-end mb-6">
                            <button onClick={onClose} className="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center hover:bg-gray-100 transition-colors">
                                <i className="bi bi-x-lg"></i>
                            </button>
                        </div>

                        {/* Discount Section */}
                        <div className="mb-8">
                            <div className="flex justify-between items-center mb-4">
                                <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Diskon & Kupon</label>
                                {!canUseCoupon && <span className="text-[8px] font-bold text-orange-400 uppercase bg-orange-50 px-2 py-1 rounded-md">Kupon khusus Member/Owner</span>}
                            </div>

                            <div className="flex gap-2 mb-3">
                                <button onClick={() => setDiscountType('nominal')} className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${discountType === 'nominal' ? 'bg-orange-500 text-white shadow-lg' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'}`}>Nominal</button>
                                <button onClick={() => setDiscountType('percent')} className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${discountType === 'percent' ? 'bg-orange-500 text-white shadow-lg' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'}`}>Persen</button>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <input
                                    type="number"
                                    value={discountValue}
                                    onChange={(e) => setDiscountValue(parseFloat(e.target.value) || 0)}
                                    placeholder="Input Diskon..."
                                    className="w-full bg-gray-50 border-none rounded-2xl p-4 font-black text-lg focus:ring-2 focus:ring-orange-500 transition-all"
                                />
                                <input
                                    type="text"
                                    disabled={!canUseCoupon}
                                    value={couponCode}
                                    onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                                    placeholder="Kode Kupon..."
                                    className="w-full bg-gray-50 border-none rounded-2xl p-4 font-black text-lg focus:ring-2 focus:ring-orange-500 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                />
                            </div>
                        </div>

                        <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 block">Metode Pembayaran</label>
                        <div className="grid grid-cols-2 gap-3 mb-8">
                            {[
                                { id: 'Tunai', icon: 'bi-cash-stack' },
                                { id: 'QRIS', icon: 'bi-qr-code-scan' },
                                { id: 'EDC', icon: 'bi-credit-card-2-front' },
                                { id: 'INVOICE', icon: 'bi-file-earmark-text' }
                            ].map(m => (
                                <button
                                    key={m.id}
                                    onClick={() => setPaymentMethod(m.id)}
                                    className={`py-4 rounded-2xl font-black transition-all flex items-center gap-4 px-6 border-2 ${method === m.id ? 'border-orange-500 bg-orange-50 text-orange-500 shadow-lg shadow-orange-500/10' : 'border-gray-100 bg-white text-gray-400 hover:border-gray-200'}`}
                                >
                                    <i className={`bi ${m.icon} text-xl`}></i>
                                    <span className="text-xs uppercase tracking-widest">{m.id}</span>
                                </button>
                            ))}
                        </div>

                        {method === 'Tunai' && (
                            <div className="mb-8 animate-in slide-in-from-top duration-300">
                                <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 block">Jumlah Bayar (Tunai)</label>
                                <div className="flex items-center gap-4">
                                    <input
                                        type="number"
                                        value={amountPaid}
                                        onChange={(e) => setAmountPaid(parseFloat(e.target.value) || 0)}
                                        className="flex-1 bg-gray-50 border-none rounded-2xl p-4 font-black text-xl focus:ring-2 focus:ring-orange-500 transition-all"
                                    />
                                    <button onClick={() => setAmountPaid(finalTotal)} className="px-6 py-4 bg-gray-100 rounded-2xl font-bold text-xs hover:bg-gray-200 transition-all">Uang Pas</button>
                                </div>
                                {amountPaid > finalTotal && (
                                    <div className="mt-4 flex justify-between items-center text-green-600 font-black">
                                        <span className="text-xs uppercase tracking-widest">Kembalian</span>
                                        <span>Rp {new Intl.NumberFormat('id-ID').format(amountPaid - finalTotal)}</span>
                                    </div>
                                )}
                            </div>
                        )}

                        <button
                            disabled={processing || (method === 'Tunai' && amountPaid < finalTotal)}
                            onClick={handleFinalize}
                            className={`w-full py-5 text-white rounded-[2rem] font-black text-xl shadow-xl transition-all active:scale-95 disabled:opacity-30 disabled:shadow-none ${method === 'QRIS' ? 'bg-gradient-to-r from-blue-500 to-indigo-600 shadow-blue-500/30' : method === 'INVOICE' ? 'bg-gradient-to-r from-purple-600 to-indigo-700 shadow-purple-500/30' : 'bg-gradient-to-r from-orange-500 to-yellow-400 shadow-orange-500/30'}`}
                        >
                            {processing ? 'Memproses...' : method === 'QRIS' ? 'Generate QRIS' : method === 'INVOICE' ? 'Settle to Invoice' : 'Selesaikan Pembayaran'}
                        </button>
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
            <div className="fixed inset-0 z-[9000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                    <div className="p-8">
                        <h2 className="text-2xl font-black text-gray-900 tracking-tight mb-2">Void Pesanan</h2>
                        <p className="text-gray-400 font-medium text-sm leading-relaxed mb-6">Masukkan alasan void dan PIN manager untuk melanjutkan.</p>

                        <div className="mb-4">
                            <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Alasan</label>
                            <input value={reason} onChange={(e) => setReason(e.target.value)} className="w-full bg-gray-50 rounded-2xl p-4" placeholder="Alasan void..." />
                        </div>

                        <div className="mb-6">
                            <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">PIN Manager</label>
                            <input type="password" value={pin} onChange={(e) => setPin(e.target.value)} className="w-full bg-gray-50 rounded-2xl p-4" placeholder="PIN..." />
                        </div>

                        <div className="flex gap-3">
                            <button onClick={onClose} className="flex-1 py-3 bg-gray-100 text-gray-600 rounded-2xl font-black">Batal</button>
                            <button disabled={processing} onClick={handleSubmit} className="flex-1 py-3 bg-red-500 text-white rounded-2xl font-black">{processing ? 'Memproses...' : 'Void Pesanan'}</button>
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
            <div className="fixed inset-0 z-[9000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                <div className="bg-white w-full max-w-sm rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300 text-black">
                    <div className="p-8">
                        <h2 className="text-2xl font-black tracking-tight mb-2">Void Item: {target?.item?.menu_name}</h2>
                        <p className="text-gray-600 font-medium text-sm leading-relaxed mb-6">Pilih jumlah item yang akan di-VOID (maks {target?.item?.qty}).</p>

                        <div className="mb-4">
                            <label className="text-[10px] font-black text-black uppercase tracking-widest mb-2 block">Jumlah yang di-void</label>
                            <input type="number" min="1" max={target?.item?.qty} value={qty} onChange={(e)=> setQty(Math.max(1, Math.min(target?.item?.qty || 1, parseInt(e.target.value || 0))))} className="w-full bg-gray-50 rounded-2xl p-4 text-black placeholder-gray-400" />
                        </div>

                        <div className="mb-6">
                            <label className="text-[10px] font-black text-black uppercase tracking-widest mb-2 block">Alasan (opsional)</label>
                            <input value={reason} onChange={(e) => setReason(e.target.value)} className="w-full bg-gray-50 rounded-2xl p-4 text-black placeholder-gray-400" placeholder="Alasan..." />
                        </div>

                        <div className="flex gap-3">
                            <button onClick={onClose} className="flex-1 py-3 bg-gray-100 text-gray-600 rounded-2xl font-black">Batal</button>
                            <button disabled={processing} onClick={handle} className="flex-1 py-3 bg-red-500 text-white rounded-2xl font-black">{processing ? 'Memproses...' : 'Void Item'}</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const SidebarIcon = ({ icon, label, active = false, onClick, count = 0 }) => (
        <div
            onClick={onClick}
            className={`relative flex flex-col items-center justify-center w-full py-5 cursor-pointer transition-all duration-200 group ${active ? 'text-orange-500' : 'text-gray-500 hover:text-orange-400'}`}
        >
            {active && <div className="absolute left-0 top-0 bottom-0 w-1.5 bg-orange-500 rounded-r-full shadow-[2px_0_10px_rgba(249,115,22,0.4)]"></div>}
            <div className={`p-2 rounded-xl transition-all ${active ? 'bg-orange-500/10' : 'group-hover:bg-gray-800'}`}>
                <i className={`bi ${icon} text-2xl`}></i>
                {count > 0 && (
                    <span className="absolute top-4 right-4 bg-red-500 text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center animate-pulse">
                        {count}
                    </span>
                )}
            </div>
            <span className="text-[10px] font-black uppercase tracking-tighter mt-1">{label}</span>
        </div>
    );

    const OrderTypeCard = ({ icon, title, subtitle, onClick, color = "orange" }) => (
        <div
            onClick={onClick}
            className="w-full max-w-[340px] aspect-square bg-white rounded-[2.5rem] p-10 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 transform hover:-translate-y-3 hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] group border-2 border-transparent hover:border-orange-100"
        >
            <div className={`w-24 h-24 rounded-3xl flex items-center justify-center mb-8 transition-transform group-hover:scale-110 ${color === 'orange' ? 'bg-orange-50 text-orange-500' : 'bg-green-50 text-green-500'}`}>
                <i className={`bi ${icon} text-5xl`}></i>
            </div>
            <h2 className="text-3xl font-black text-gray-900 tracking-tight">{title}</h2>
            <p className="text-gray-400 font-medium mt-2">{subtitle}</p>
            <div className={`mt-10 flex items-center gap-2 font-bold text-sm ${color === 'orange' ? 'text-orange-500' : 'text-green-500'}`}>
                {title === 'Dine In' ? 'Pilih Meja' : 'Langsung Pesan'} <i className="bi bi-arrow-right"></i>
            </div>
        </div>
    );

    const TableCard = ({ table, active, onClick, guestCount }) => {
        const statusConfig = {
            available: { bg: 'bg-green-50', border: 'border-green-100', text: 'text-green-600', label: 'Tersedia' },
            occupied: { bg: 'bg-gray-50', border: 'border-gray-200', text: 'text-gray-400', label: 'Terisi' },
            reserved: { bg: 'bg-orange-50', border: 'border-orange-100', text: 'text-orange-500', label: 'Reservasi' }
        };
        const config = statusConfig[table.status] || statusConfig.available;

        // Debugging capacity
        const capacity = table.capacity || table.seats || 0;
        const isClickable = table.status === 'available' && capacity >= guestCount;

        return (
            <div
                onClick={() => {
                    if (isClickable) {
                        onClick(table);
                    } else if (table.status !== 'available') {
                        console.log(`Table ${table.name} is not available (${table.status})`);
                    } else if (capacity < guestCount) {
                        console.log(`Table ${table.name} capacity (${capacity}) is less than guest count (${guestCount})`);
                    }
                }}
                className={`relative p-6 rounded-[2rem] border-2 transition-all duration-300 flex flex-col justify-between aspect-video ${active ? 'border-orange-500 bg-orange-50 shadow-lg' : config.border + ' ' + config.bg} ${isClickable ? 'cursor-pointer hover:shadow-md' : 'opacity-60 cursor-not-allowed'}`}
            >
                <div className="flex justify-between items-start">
                    <div className={`w-12 h-12 rounded-2xl flex items-center justify-center ${active ? 'bg-orange-500 text-white' : 'bg-white ' + config.text}`}>
                        <i className="bi bi-grid-fill text-xl"></i>
                    </div>
                    {active && <i className="bi bi-check-circle-fill text-orange-500 text-xl"></i>}
                </div>
                <div>
                    <h3 className={`text-2xl font-black tracking-tight ${active ? 'text-orange-600' : 'text-gray-900'}`}>{table.name}</h3>
                    <div className="flex items-center gap-2 mt-1">
                        <i className="bi bi-people-fill text-xs text-gray-400"></i>
                        <span className="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Maks. {capacity}</span>
                    </div>
                </div>
                <div className={`mt-4 inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest ${config.text}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${active ? 'bg-orange-500' : config.text.replace('text-', 'bg-')}`}></div>
                    {config.label}
                </div>
            </div>
        );
    };

    // --- Main Views ---

    const OrderTypeView = ({ onSelect }) => (
        <div className="w-full h-full flex flex-col items-center justify-center bg-[#daaa64] animate-in fade-in zoom-in duration-500">
            <h1 className="text-5xl font-black text-gray-900 tracking-tighter mb-2">Selamat Datang!</h1>
            <p className="text-xl text-gray-400 font-medium mb-16 tracking-tight">Pilih jenis pesanan Anda untuk memulai</p>
            <div className="flex gap-10 w-full max-w-4xl px-6">
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

    const TableSelectionView = ({ tables, guestCount, setGuestCount, selectedTable, onSelect, onBack, onContinue }) => (
        <div className="w-full h-full flex bg-gray-50 animate-in slide-in-from-right duration-500">
            {/* Left: Guest Control */}
            <div className="w-[320px] bg-white border-r border-gray-100 p-8 flex flex-col">
                <button onClick={onBack} className="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-sm mb-10 transition-colors">
                    <i className="bi bi-arrow-left"></i> Kembali
                </button>

                <div className="flex items-center gap-3 mb-2">
                    <div className="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-black text-xs">2</div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Pilih Meja</h2>
                </div>

                <div className="mt-8">
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 block">Jumlah Tamu</label>
                    <div className="flex items-center justify-between bg-gray-50 rounded-3xl p-4 border border-gray-100 mb-6">
                        <button onClick={() => setGuestCount(Math.max(1, guestCount - 1))} className="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-gray-900 hover:bg-orange-500 hover:text-white transition-all active:scale-90">
                            <i className="bi bi-dash-lg text-xl"></i>
                        </button>
                        <span className="text-5xl font-black text-gray-900 w-20 text-center tracking-tighter">{guestCount}</span>
                        <button onClick={() => setGuestCount(guestCount + 1)} className="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-gray-900 hover:bg-orange-500 hover:text-white transition-all active:scale-90">
                            <i className="bi bi-plus-lg text-xl"></i>
                        </button>
                    </div>

                    <div className="grid grid-cols-4 gap-2 mb-10">
                        {[1, 2, 3, 4, 5, 6, 8, 10].map(n => (
                            <button
                                key={n}
                                onClick={() => setGuestCount(n)}
                                className={`py-3 rounded-xl font-black transition-all ${guestCount === n ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'}`}
                            >
                                {n}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="mt-auto space-y-3 pt-6 border-t border-gray-50">
                    <div className="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <div className="w-3 h-3 rounded-full bg-green-500"></div> Tersedia
                    </div>
                    <div className="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <div className="w-3 h-3 rounded-full bg-gray-300"></div> Terisi
                    </div>
                    <div className="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <div className="w-3 h-3 rounded-full bg-orange-500"></div> Reservasi
                    </div>
                </div>
            </div>

            {/* Right: Table Grid */}
            <div className="flex-1 p-10 flex flex-col overflow-hidden">
                <div className="flex justify-between items-center mb-8">
                    <h3 className="text-xl font-black text-gray-900 tracking-tight">Meja tersedia untuk {guestCount}+ tamu</h3>
                    <div className="bg-white px-4 py-2 rounded-full border border-gray-100 text-[10px] font-black text-gray-500 uppercase tracking-widest">
                        {tables.filter(t => t.status === 'available' && t.capacity >= guestCount).length} tersedia
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto pr-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start custom-scrollbar">
                    {tables.map(table => (
                        <TableCard
                            key={table.id}
                            table={table}
                            active={selectedTable?.id === table.id}
                            guestCount={guestCount}
                            onClick={onSelect}
                        />
                    ))}
                </div>

                <div className="mt-8 flex justify-end">
                    <button
                        disabled={!selectedTable}
                        onClick={onContinue}
                        className="px-12 py-5 bg-gradient-to-r from-orange-500 to-yellow-400 text-white rounded-[2rem] font-black text-lg shadow-xl shadow-orange-500/20 disabled:opacity-30 disabled:shadow-none transition-all active:scale-95"
                    >
                        Lanjut ke Menu <i className="bi bi-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    );

    const MenuView = ({ menuItems, categories, orderType, selectedTable, guestCount, onBack, onShowToast }) => {
        const [activeCategory, setActiveCategory] = useState('Makanan');
        const [cart, setCart] = useState([]);
        const [searchQuery, setSearchQuery] = useState('');
        const [customerName, setCustomerName] = useState('');
        const [customerCategory, setCustomerCategory] = useState('Umum');
        const [submitting, setSubmitting] = useState(false);

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
                const res = await fetch('/terminal/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        table_id: selectedTable ? selectedTable.id : 1,
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
                    await fetch(`/terminal/orders/${data.id}/submit-to-cashier`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });

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
                <div className="flex-1 flex flex-col overflow-hidden p-8">
                    <div className="flex items-center gap-6 mb-8">
                        <button onClick={onBack} className="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-sm transition-colors">
                            <i className="bi bi-arrow-left"></i> Kembali
                        </button>
                        <div className="flex gap-2">
                            <div className="bg-green-100 text-green-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                <i className="bi bi-check-circle-fill"></i> Tipe Order: {orderType === 'DINE_IN' ? 'Dine In' : 'Take Away'}
                            </div>
                            {selectedTable && (
                                <div className="bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                    <i className="bi bi-check-circle-fill"></i> Meja: {selectedTable.name}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Category Tabs & Search */}
                    <div className="flex justify-between items-center mb-8 gap-4">
                        <div className="flex gap-3 overflow-x-auto pb-2 custom-scrollbar no-scrollbar flex-1">
                            {mainCategories.map(cat => (
                                <button
                                    key={cat}
                                    onClick={() => setActiveCategory(cat)}
                                    className={`px-8 py-3 rounded-2xl font-black text-sm transition-all whitespace-nowrap ${activeCategory === cat ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'bg-white text-gray-400 hover:bg-gray-100'}`}
                                >
                                    {cat}
                                </button>
                            ))}
                        </div>
                        <div className="relative w-64">
                            <i className="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input
                                type="text"
                                placeholder="Cari menu..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full bg-white border-none rounded-2xl py-3 pl-12 pr-4 text-sm font-bold shadow-sm focus:ring-2 focus:ring-orange-500 transition-all"
                            />
                        </div>
                    </div>

                    {/* Menu Grid */}
                    <div className="flex-1 overflow-y-auto pr-2 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start custom-scrollbar">
                        {filteredMenu.length === 0 ? (
                            <div className="col-span-full h-full flex flex-col items-center justify-center opacity-20 py-20">
                                <i className="bi bi-egg-fried text-7xl mb-4"></i>
                                <p className="font-black uppercase tracking-widest">Tidak ada menu di kategori ini</p>
                            </div>
                        ) : (
                            filteredMenu.map(item => (
                                <div
                                    key={item.id}
                                    onClick={() => addToCart(item)}
                                    className="bg-white rounded-[2rem] p-5 flex flex-col cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-2 group"
                                >
                                    <div className="aspect-square rounded-2xl overflow-hidden mb-4 bg-gray-50">
                                        <img src={item.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=200&h=200&auto=format&fit=crop'} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                                    </div>
                                    <h4 className="text-lg font-black text-gray-900 leading-tight mb-2">{item.name}</h4>
                                    <div className="mt-auto flex justify-between items-center">
                                        <span className="text-orange-500 font-black text-xl">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</span>
                                        <div className="w-10 h-10 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition-all">
                                            <i className="bi bi-plus-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                {/* Right: Cart Area */}
                <div className="w-[420px] bg-white border-l border-gray-100 flex flex-col p-8 shadow-[-10px_0_30px_rgba(0,0,0,0.02)]">
                    <div className="flex items-center gap-3 mb-8">
                        <i className="bi bi-cart-fill text-2xl text-orange-500"></i>
                        <h2 className="text-2xl font-black text-gray-900 tracking-tight">Pesanan Baru</h2>
                    </div>

                    {/* Customer Info Section */}
                    <div className="space-y-6 mb-8">
                        <div>
                            <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 block">Nama Pelanggan</label>
                            <input
                                type="text"
                                placeholder="Input nama..."
                                value={customerName}
                                onChange={(e) => setCustomerName(e.target.value)}
                                className="w-full bg-gray-50 border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-orange-500 transition-all"
                            />
                        </div>
                        <div>
                            <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 block">Kategori Pelanggan</label>
                            <select
                                value={customerCategory}
                                onChange={(e) => setCustomerCategory(e.target.value)}
                                className="w-full bg-gray-50 border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-orange-500 transition-all appearance-none cursor-pointer"
                            >
                                {['Reguler', 'Member', 'Staff', 'Majar Owner'].map(cat => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4">
                        {cart.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center opacity-30">
                                <i className="bi bi-cart-x text-6xl mb-4"></i>
                                <p className="font-bold text-sm uppercase tracking-widest">Belum ada produk</p>
                                <p className="text-[10px] mt-1">Ketuk produk untuk menambahkan</p>
                            </div>
                        ) : (
                            cart.map(item => (
                                <div key={item.id} className="bg-gray-50 rounded-2xl p-4 flex gap-4 animate-in slide-in-from-bottom duration-300">
                                    <img src={item.image_url} className="w-16 h-16 rounded-xl object-cover" />
                                    <div className="flex-1">
                                        <h5 className="font-black text-gray-900 text-sm leading-tight">{item.name}</h5>
                                        <p className="text-orange-500 font-bold text-xs mt-1">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</p>
                                        <div className="flex items-center gap-3 mt-3">
                                            <button onClick={() => updateQty(item.id, -1)} className="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-900 hover:bg-red-50 hover:text-red-500 transition-all"><i className="bi bi-dash"></i></button>
                                            <span className="font-black text-gray-900 w-6 text-center">{item.qty}</span>
                                            <button onClick={() => updateQty(item.id, 1)} className="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-900 hover:bg-green-50 hover:text-green-500 transition-all"><i className="bi bi-plus"></i></button>
                                        </div>
                                    </div>
                                    <div className="text-right font-black text-gray-900">
                                        Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    <div className="mt-8 pt-8 border-t-2 border-dashed border-gray-100 space-y-4">
                        <div className="flex justify-between items-center text-gray-400 font-bold">
                            <span className="text-[10px] uppercase tracking-widest">Subtotal</span>
                            <span>Rp {new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                        </div>
                        <div className="flex justify-between items-center">
                            <span className="text-lg font-black text-gray-900 uppercase tracking-tighter">Total Bill</span>
                            <span className="text-3xl font-black text-orange-500 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                        </div>
                        <button
                            disabled={cart.length === 0 || submitting}
                            onClick={handleSendToKitchen}
                            className="w-full py-5 bg-gradient-to-r from-[#063024] to-[#0a4d3a] text-white rounded-[2rem] font-black text-xl shadow-xl shadow-[#063024]/20 transition-all active:scale-95 disabled:opacity-30 disabled:shadow-none mt-4"
                        >
                            {submitting ? 'Mengirim...' : 'Kirim ke Kitchen'} <i className="bi bi-send-fill ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    // --- Main Terminal App ---

    const KasirTerminal = () => {
        const [view, setView] = useState('ORDER_TYPE'); // ORDER_TYPE, TABLE_SELECT, MENU
        const [orderType, setOrderType] = useState('DINE_IN');
        const [guestCount, setGuestCount] = useState(2);
        const [selectedTable, setSelectedTable] = useState(null);
        const [tables, setTables] = useState(@json($tables));
        const [loadingTables, setLoadingTables] = useState(false);
        const [activeOrders, setActiveOrders] = useState([]);
        const [selectedOrder, setSelectedOrder] = useState(null);
        const [showPaymentModal, setShowPaymentModal] = useState(false);
        const [showVoidModal, setShowVoidModal] = useState(false);
        const [showVoidItemModal, setShowVoidItemModal] = useState(false);
        const [voidItemTarget, setVoidItemTarget] = useState(null);
        const [toast, setToast] = useState(null);
        const [confirmAction, setConfirmAction] = useState(null); // { title, message, onConfirm }

        const fetchTables = useCallback(async () => {
            try {
                const res = await fetch('/terminal/tables');
                const data = await res.json();
                setTables(data || []);
            } catch (e) { console.error(e); }
        }, []);

        const fetchActiveOrders = useCallback(async () => {
            try {
                const res = await fetch('/terminal/orders?role=kasir');
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
                        const res = await fetch(`/terminal/orders/${orderId}/items/${itemId}/void`, {
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

        const handleFinalizePayment = async (method, amount, discount = 0) => {
            try {
                const res = await fetch(`/terminal/orders/${selectedOrder.id}/finalize-payment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        payment_method: method.toLowerCase(),
                        amount_paid: amount,
                        discount_amount: discount
                    })
                });

                const data = await res.json();
                if (data.success) {
                    onShowToast(method === 'INVOICE' ? 'Pesanan dipindahkan ke Invoice!' : 'Pembayaran Berhasil! Meja tersedia.');
                    setShowPaymentModal(false);
                    setSelectedOrder(null);
                    fetchActiveOrders();
                    fetchTables();
                } else {
                    onShowToast(data.error || 'Gagal memproses pembayaran', 'error');
                }
            } catch (e) {
                onShowToast('Terjadi kesalahan sistem.', 'error');
            }
        };

        const handleVoidOrder = async (orderId, reason, pin) => {
            try {
                const res = await fetch(`/terminal/orders/${orderId}/void`, {
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
                const res = await fetch(`/terminal/orders/${orderId}/items/${itemId}/void`, {
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
                        const updated = (await (await fetch(`/terminal/orders?role=kasir`)).json()).find(o => o.id === selectedOrder.id);
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
                setSelectedTable(null);
                setView('MENU');
            }
        };

        const handleSelectTable = (table) => {
            setSelectedTable(table);
        };

        const handleBack = () => {
            if (view === 'MENU' && orderType === 'TAKE_AWAY') setView('ORDER_TYPE');
            else if (view === 'MENU') setView('TABLE_SELECT');
            else if (view === 'TABLE_SELECT') setView('ORDER_TYPE');
            else if (view === 'ORDER_STATUS' || view === 'ORDER_HISTORY') setView('ORDER_TYPE');
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
                            guestCount={guestCount}
                            setGuestCount={setGuestCount}
                            selectedTable={selectedTable}
                            onSelect={handleSelectTable}
                            onBack={handleBack}
                            onContinue={() => setView('MENU')}
                        />
                    );
                case 'MENU':
                    return (
                        <MenuView
                            menuItems={ @json($menuItems) }
                            categories={ @json($categories) }
                            orderType={orderType}
                            selectedTable={selectedTable}
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
                {/* Fixed Sidebar */}
                <div className="w-80 bg-[#063024] flex flex-col border-r border-[#063024] shadow-2xl z-50">
                    <div className="p-8 border-b border-white/5">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-orange-500/30">
                                <span className="font-black text-xl text-white">M</span>
                            </div>
                            <div>
                                <h1 className="text-white font-black tracking-tight">MAJAR POS</h1>
                                <p className="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Signature Mode</p>
                            </div>
                        </div>
                    </div>

                    <div className="flex-1 flex flex-col overflow-hidden">
                        {/* Navigation */}
                        <div className="p-4 grid grid-cols-3 gap-2">
                            <SidebarIcon icon="bi-plus-circle" label="Pesan" active={view === 'ORDER_TYPE' || view === 'TABLE_SELECT' || view === 'MENU'} onClick={() => setView('ORDER_TYPE')} />
                            <SidebarIcon icon="bi-shield-lock" label="Approval" active={view === 'PENDING_APPROVAL'} onClick={() => setView('PENDING_APPROVAL')} count={activeOrders.filter(o => o.stage === 'WAITING_CASHIER').length} />
                            <SidebarIcon icon="bi-clock-history" label="History" active={view === 'ORDER_HISTORY'} onClick={() => setView('ORDER_HISTORY')} />
                        </div>

                        {/* Active Orders List */}
                        <div className="flex-1 flex flex-col overflow-hidden border-t border-white/5">
                            <div className="p-6 flex justify-between items-center">
                                <h3 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Monitoring Order</h3>
                                <span className="bg-orange-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full">{activeOrders.filter(o => o.stage !== 'WAITING_CASHIER').length}</span>
                            </div>

                            <div className="flex-1 overflow-y-auto custom-scrollbar px-4 space-y-3 pb-8">
                                {activeOrders.filter(o => o.stage !== 'WAITING_CASHIER').length === 0 ? (
                                    <div className="py-12 flex flex-col items-center justify-center opacity-20 text-white">
                                        <i className="bi bi-inbox text-4xl mb-2"></i>
                                        <p className="text-[10px] font-black uppercase tracking-widest text-center px-8 leading-loose">Tidak ada pesanan aktif</p>
                                    </div>
                                ) : (
                                    activeOrders.filter(o => o.stage !== 'WAITING_CASHIER').map(order => (
                                        <div
                                            key={order.id}
                                            onClick={() => setSelectedOrder(order)}
                                            className={`p-5 rounded-[1.5rem] cursor-pointer transition-all duration-300 border-2 ${selectedOrder?.id === order.id ? 'bg-orange-500 border-orange-500 shadow-lg shadow-orange-500/20' : 'bg-white/5 border-transparent hover:bg-white/10'}`}
                                        >
                                            <div className="flex justify-between items-start mb-3">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <h4 className={`font-black text-sm ${selectedOrder?.id === order.id ? 'text-white' : 'text-gray-200'}`}>Meja {order.table?.name || 'TA'}</h4>
                                                        <span className={`px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest ${selectedOrder?.id === order.id ? 'bg-white/20 text-white' : 'bg-orange-500/20 text-orange-500'}`}>
                                                            {order.guest_category || 'Umum'}
                                                        </span>
                                                    </div>
                                                    <p className={`text-[10px] font-bold uppercase tracking-widest ${selectedOrder?.id === order.id ? 'text-orange-100' : 'text-gray-500'}`}>{order.customer_name || 'Guest'}</p>
                                                </div>
                                                <span className={`text-[10px] font-black px-2 py-1 rounded-lg ${selectedOrder?.id === order.id ? 'bg-white/20 text-white' : 'bg-gray-800 text-gray-400'}`}>
                                                    {order.stage === 'SERVED' ? 'READY TO PAY' : order.stage.replace(/_/g, ' ')}
                                                </span>
                                            </div>
                                            <div className={`text-lg font-black ${selectedOrder?.id === order.id ? 'text-white' : 'text-orange-500'}`}>
                                                Rp {new Intl.NumberFormat('id-ID').format(order.total)}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Selected Order Detail Drawer / Section */}
                    {selectedOrder && (
                        <div className="bg-white rounded-t-[2.5rem] p-8 shadow-2xl animate-in slide-in-from-bottom duration-500 max-h-[70%] flex flex-col">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-xl font-black text-gray-900 tracking-tight">Detail Tagihan</h3>
                                <button onClick={() => setSelectedOrder(null)} className="text-gray-400 hover:text-gray-900"><i className="bi bi-x-lg"></i></button>
                            </div>

                            <div className="flex-1 overflow-y-auto custom-scrollbar space-y-4 mb-6 pr-2">
                                {selectedOrder.items.map((item, idx) => (
                                    <div key={idx} className="flex justify-between items-center group">
                                        <div className="flex-1">
                                            <h5 className="font-bold text-gray-900 text-sm leading-tight">{item.qty}x {item.menu_name}</h5>
                                            <p className="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</p>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <span className={`text-[8px] font-black px-1.5 py-0.5 rounded uppercase ${
                                                item.status === 'ready' ? 'bg-green-100 text-green-600' :
                                                item.status === 'served' ? 'bg-purple-100 text-purple-600' :
                                                'bg-blue-100 text-blue-600'
                                            }`}>
                                                {item.status}
                                            </span>
                                            <span className="font-black text-gray-900 text-sm">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</span>
                                            <button
                                                onClick={() => { setVoidItemTarget({ orderId: selectedOrder.id, item }); setShowVoidItemModal(true); }}
                                                className="w-8 h-8 rounded-full bg-red-50 text-red-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-500 hover:text-white"
                                            >
                                                <i className="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="pt-6 border-t border-gray-100 space-y-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Bill</span>
                                    <span className="text-2xl font-black text-gray-900 tracking-tighter">Rp {new Intl.NumberFormat('id-ID').format(selectedOrder.total)}</span>
                                </div>
                                <div className="flex gap-3">
                                    <button
                                        onClick={() => setShowPaymentModal(true)}
                                        className="flex-1 py-4 bg-orange-500 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-orange-500/30 transition-all active:scale-95"
                                    >
                                        Selesaikan Pembayaran
                                    </button>
                                    <button
                                        onClick={() => setShowVoidModal(true)}
                                        className="flex-1 py-4 bg-red-500 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-red-500/30 transition-all active:scale-95"
                                    >
                                        Void Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Content Area */}
                <div className="flex-1 h-full overflow-hidden">
                    {renderView()}
                </div>

                {/* Modals & Toasts */}
                {showPaymentModal && selectedOrder && (
                    <PaymentModal
                        order={selectedOrder}
                        onConfirm={handleFinalizePayment}
                        onClose={() => setShowPaymentModal(false)}
                    />
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
                {toast && <Toast {...toast} onClose={() => setToast(null)} />}
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
                const res = await fetch(`/terminal/orders/${orderId}/approve`, {
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

        return (
            <div className="w-full h-full flex flex-col p-8 bg-gray-50 animate-in fade-in duration-500 overflow-hidden">
                <div className="flex items-center gap-4 mb-8">
                    <button onClick={onBack} className="text-gray-400 hover:text-gray-900"><i className="bi bi-arrow-left text-2xl"></i></button>
                    <h1 className="text-3xl font-black text-gray-900 tracking-tighter">Pending Approval</h1>
                </div>

                <div className="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 content-start custom-scrollbar pr-2">
                    {pending.length === 0 ? (
                        <div className="col-span-full h-64 flex flex-col items-center justify-center opacity-20">
                            <i className="bi bi-shield-check text-7xl"></i>
                            <p className="font-bold uppercase tracking-widest mt-4">Semua pesanan sudah disetujui</p>
                        </div>
                    ) : (
                        pending.map(order => (
                            <div key={order.id} className="bg-white rounded-[2.5rem] p-6 shadow-sm border border-gray-100 flex flex-col h-fit">
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 className="font-black text-gray-900 text-xl">Meja {order.table?.name || 'TA'}</h4>
                                        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{order.customer_name} • {order.guest_category}</p>
                                    </div>
                                    <span className="bg-orange-100 text-orange-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Waiting</span>
                                </div>

                                    <div className="space-y-2 mb-6 border-y border-gray-50 py-4">
                                    {order.items.map((item, idx) => (
                                        <div key={idx} className="flex justify-between items-center text-xs font-bold text-gray-600">
                                            <div className="flex items-center gap-4">
                                                <span>{item.qty}x {item.menu_name}</span>
                                                <span className="text-[10px] text-gray-400">Rp {new Intl.NumberFormat('id-ID').format(item.price)}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-gray-900">Rp {new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</span>
                                                <button
                                                    onClick={() => onVoidItem(order.id, item)}
                                                    className="w-8 h-8 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all"
                                                    title="Void Item"
                                                >
                                                    <i className="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                    <div className="flex justify-between pt-2 font-black text-gray-900 text-sm">
                                        <span>Total</span>
                                        <span>Rp {new Intl.NumberFormat('id-ID').format(order.total)}</span>
                                    </div>
                                </div>

                                <button
                                    disabled={processing}
                                    onClick={() => handleApprove(order.id)}
                                    className="w-full py-4 bg-green-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest active:scale-95 transition-all shadow-xl shadow-green-500/20"
                                >
                                    {processing ? 'Memproses...' : 'Approve & Kirim ke Dapur'}
                                </button>
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
                const res = await fetch(`/terminal/orders?role=${role}`);
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
                        const res = await fetch(`/terminal/orders/${id}/approve`, {
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

        useEffect(() => {
            fetch('/terminal/orders/history')
                .then(res => res.json())
                .then(data => {
                    setHistory(data);
                    setLoading(false);
                });
        }, []);

        return (
            <div className="w-full h-full flex flex-col p-8 bg-gray-50 animate-in fade-in duration-500 overflow-hidden">
                <div className="flex items-center gap-4 mb-8">
                    <button onClick={onBack} className="text-gray-400 hover:text-gray-900"><i className="bi bi-arrow-left text-2xl"></i></button>
                    <h1 className="text-3xl font-black text-gray-900 tracking-tighter">History Hari Ini</h1>
                </div>

                <div className="flex-1 overflow-y-auto bg-white rounded-[2rem] border border-gray-100 shadow-sm custom-scrollbar">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="border-b border-gray-50">
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Waktu</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Kode</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Meja</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {history.map(order => (
                                <tr key={order.id} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                    <td className="p-6 text-sm font-bold text-gray-600">{new Date(order.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}</td>
                                    <td className="p-6 text-sm font-black text-gray-900">{order.code}</td>
                                    <td className="p-6 text-sm font-bold text-gray-600">{order.table?.name || 'TA'}</td>
                                    <td className="p-6 text-sm font-black text-orange-500">Rp {new Intl.NumberFormat('id-ID').format(order.total)}</td>
                                    <td className="p-6">
                                        <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${order.stage === 'DONE' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'}`}>
                                            {order.stage}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
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
