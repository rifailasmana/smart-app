@extends('layouts.terminal')

@section('title', 'Waiter - Majar Signature')
@section('terminal_role', 'WAITER')

@section('header_extra')
    <div class="flex items-center gap-4 border-l border-gray-700 pl-4">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">System: Online</span>
        </div>
    </div>
@endsection

@section('content')
    <div class="w-full h-full" id="waiter-root">
        <div style="margin:auto; max-width: 520px; padding: 24px; text-align:center;">
            <div style="font-weight: 800; font-size: 20px; letter-spacing: .02em;">Memuat Terminal Waiter…</div>
            <div style="margin-top: 8px; opacity: .75; font-size: 14px; line-height: 1.4;">Jika layar tetap kosong, coba muat
                ulang.</div>
            <div style="margin-top: 16px; display:flex; gap: 10px; justify-content:center; flex-wrap: wrap;">
                <button type="button"
                    style="padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255,140,0,.35); background: #fff; font-weight: 700;"
                    onclick="location.reload()">Muat Ulang</button>
                <a href="{{ route('terminal.index') }}"
                    style="padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255,140,0,.35); background: transparent; font-weight: 700; text-decoration:none; color: inherit;">Pilih
                    Terminal</a>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script type="text/babel" data-presets="env,react" data-plugins="proposal-optional-chaining,proposal-nullish-coalescing-operator">
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

    const ConfirmModal = ({ title, message, onConfirm, onClose, confirmText = 'Konfirmasi', cancelText = 'Batal', type = 'info' }) => (
        <div className="fixed inset-0 z-[9000] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
            <div className="bg-white w-full max-w-sm rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                <div className="p-8 text-center">
                    <div className={`w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6 ${type === 'danger' ? 'bg-red-50 text-red-500' : 'bg-orange-50 text-orange-500'}`}>
                        <i className={`bi ${type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill'} text-4xl`}></i>
                    </div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight mb-2">{title}</h2>
                    <p className="text-gray-400 font-medium text-sm leading-relaxed mb-8">{message}</p>
                    <div className="flex gap-3">
                        <button onClick={onClose} className="flex-1 py-4 bg-gray-100 text-gray-400 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-gray-200 transition-all">{cancelText}</button>
                        <button onClick={onConfirm} className={`flex-1 py-4 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl transition-all active:scale-95 ${type === 'danger' ? 'bg-red-500 shadow-red-500/30' : 'bg-orange-500 shadow-orange-500/30'}`}>{confirmText}</button>
                    </div>
                </div>
            </div>
        </div>
    );

    const SidebarIcon = ({ icon, label, active = false, onClick }) => (
        <div
            onClick={onClick}
            className={`relative flex flex-col items-center justify-center w-full py-5 cursor-pointer transition-all duration-200 group ${active ? 'text-orange-500' : 'text-gray-500 hover:text-orange-400'}`}
        >
            {active && <div className="absolute left-0 top-0 bottom-0 w-1.5 bg-orange-500 rounded-r-full shadow-[2px_0_10px_rgba(249,115,22,0.4)]"></div>}
            <div className={`p-2 rounded-xl transition-all ${active ? 'bg-orange-500/10' : 'group-hover:bg-gray-800'}`}>
                <i className={`bi ${icon} text-2xl`}></i>
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
        <div className="w-full h-full flex bg-[#daaa64] animate-in slide-in-from-right duration-500">
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
        const [activeCategory, setActiveCategory] = useState('Semua');
        const [cart, setCart] = useState([]);
        const [searchQuery, setSearchQuery] = useState('');
        const [customerName, setCustomerName] = useState('');
        const [customerCategory, setCustomerCategory] = useState('Reguler');
        const [showConfirm, setShowConfirm] = useState(false);
        const [submitting, setSubmitting] = useState(false);

        const filteredMenu = useMemo(() => {
            return menuItems.filter(item => {
                const matchCat = activeCategory === 'Semua' || item.category === activeCategory;
                const matchSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
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

        const handleConfirmOrder = async () => {
            if (cart.length === 0) return;
            if (!customerName.trim()) {
                onShowToast('Nama Pelanggan wajib diisi', 'error');
                setShowConfirm(false);
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
                    const submitRes = await fetch(`/terminal/orders/${data.id}/submit-to-cashier`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    
                    if (submitRes.ok) {
                        onShowToast('Pesanan berhasil dikirim ke Kasir!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        onShowToast('Gagal mengirim ke kasir', 'error');
                    }
                } else {
                    onShowToast(data.error || 'Gagal memproses pesanan', 'error');
                }
            } catch (e) {
                console.error(e);
                onShowToast('Terjadi kesalahan sistem.', 'error');
            } finally {
                setSubmitting(false);
                setShowConfirm(false);
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
                            <div className="bg-gray-200 text-gray-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                <i className="bi bi-people-fill"></i> {guestCount} Tamu
                            </div>
                        </div>
                    </div>

                    {/* Category Tabs & Search */}
                    <div className="flex justify-between items-center mb-8 gap-4">
                        <div className="flex gap-3 overflow-x-auto pb-2 custom-scrollbar no-scrollbar flex-1">
                            {['Semua', ...categories].map(cat => (
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
                        {filteredMenu.map(item => (
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
                        ))}
                    </div>
                </div>

                {/* Right: Cart Area */}
                <div className="w-[420px] bg-white border-l border-gray-100 flex flex-col p-8 shadow-[-10px_0_30px_rgba(0,0,0,0.02)]">
                    <div className="flex items-center gap-3 mb-8">
                        <i className="bi bi-cart-fill text-2xl text-orange-500"></i>
                        <h2 className="text-2xl font-black text-gray-900 tracking-tight">Pesanan</h2>
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
                            onClick={() => setShowConfirm(true)}
                            className="w-full py-5 bg-gradient-to-r from-orange-500 to-yellow-400 text-white rounded-[2rem] font-black text-xl shadow-xl shadow-orange-500/30 transition-all active:scale-95 disabled:opacity-30 disabled:shadow-none mt-4"
                        >
                            {submitting ? 'Mengirim...' : 'Kirim Pesanan'} <i className="bi bi-send-fill ml-2"></i>
                        </button>
                    </div>
                </div>

                {showConfirm && (
                    <ConfirmModal
                        title="Kirim Pesanan?"
                        message={`Apakah Anda yakin ingin mengirim ${cart.length} item pesanan ini ke Kasir?`}
                        onConfirm={handleConfirmOrder}
                        onClose={() => setShowConfirm(false)}
                        confirmText="Ya, Kirim"
                    />
                )}
            </div>
        );
    }

    // --- Main Terminal App ---

    const WaiterTerminal = () => {
        const [view, setView] = useState('ORDER_TYPE'); // ORDER_TYPE, TABLE_SELECT, MENU
        const [orderType, setOrderType] = useState('DINE_IN');
        const [guestCount, setGuestCount] = useState(2);
        const [selectedTable, setSelectedTable] = useState(null);
        const [tables, setTables] = useState(@json($tables));
        const [loadingTables, setLoadingTables] = useState(false);
        const [toast, setToast] = useState(null);

        const onShowToast = (message, type = 'success') => {
            setToast({ message, type });
        };

        const fetchTables = useCallback(async () => {
            try {
                const res = await fetch('/terminal/tables');
                const data = await res.json();
                setTables(data || []);
            } catch (e) { 
                console.error('Failed to fetch tables:', e); 
            } finally { 
                setLoadingTables(false); 
            }
        }, []);

        useEffect(() => {
            fetchTables();
            const interval = setInterval(fetchTables, 5000);
            return () => clearInterval(interval);
        }, [fetchTables]);

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
                case 'ORDER_STATUS':
                    return <OrderStatusView role="waiter" onBack={() => setView('ORDER_TYPE')} onShowToast={onShowToast} />;
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
                            onBack={handleBack}
                            onShowToast={onShowToast}
                        />
                    );
                case 'ORDER_TYPE':
                default:
                    return <OrderTypeView onSelect={handleSelectOrderType} />;
            }
        };

        return (
            <div className="w-full h-full flex overflow-hidden">
                {/* Fixed Sidebar */}
                <div className="w-24 bg-[#063024] flex flex-col border-r border-[#063024]">
                    <div className="p-6 border-b border-[#063024]">
                        <div className="w-full aspect-square rounded-2xl bg-gradient-to-br from-orange-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-orange-500/30">
                            <span className="font-black text-2xl text-white">S</span>
                        </div>
                    </div>
                    <div className="flex-1 py-4">
                        <SidebarIcon icon="bi-plus-circle" label="Pesan" active={view === 'ORDER_TYPE' || view === 'TABLE_SELECT' || view === 'MENU'} onClick={() => setView('ORDER_TYPE')} />
                        <SidebarIcon icon="bi-list-check" label="Status" active={view === 'ORDER_STATUS'} onClick={() => setView('ORDER_STATUS')} />
                        <SidebarIcon icon="bi-clock-history" label="History" active={view === 'ORDER_HISTORY'} onClick={() => setView('ORDER_HISTORY')} />
                    </div>
                    <div className="py-4 border-t border-[#063024]">
                        <SidebarIcon icon="bi-person-badge-fill" label="Profil" />
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1 h-full overflow-hidden bg-[#daaa68]">
                    {renderView()}
                </div>

                {toast && <Toast message={toast.message} type={toast.type} onClose={() => setToast(null)} />}
            </div>
        );
    };

    // --- Order Status & History Components ---

    const OrderStatusView = ({ role, onBack, onShowToast }) => {
        const [orders, setOrders] = useState([]);
        const [loading, setLoading] = useState(true);
        const [confirmItem, setConfirmItem] = useState(null); // { orderId, itemId, menuName }

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

        const handleItemServe = async () => {
            if (!confirmItem) return;
            try {
                const res = await fetch(`/terminal/orders/${confirmItem.orderId}/items/${confirmItem.itemId}/status`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ status: 'served' })
                });
                if (res.ok) {
                    onShowToast(`${confirmItem.menuName} berhasil disajikan!`);
                    fetchOrders();
                } else {
                    const data = await res.json();
                    onShowToast(data.error || 'Gagal menyajikan item', 'error');
                }
            } catch (e) {
                onShowToast('Terjadi kesalahan sistem', 'error');
            } finally {
                setConfirmItem(null);
            }
        };

        const handleVoidItem = async (orderId, itemId, menuName) => {
            if (!window.confirm(`Apakah Anda yakin ingin VOID ${menuName}?`)) return;
            try {
                const res = await fetch(`/terminal/orders/${orderId}/items/${itemId}/void`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    onShowToast('Item berhasil di-VOID');
                    fetchOrders();
                } else {
                    onShowToast(data.error || 'Gagal melakukan VOID', 'error');
                }
            } catch (e) {
                onShowToast('Terjadi kesalahan sistem', 'error');
            }
        };

        return (
            <div className="w-full h-full flex flex-col p-8 bg-gray-50 animate-in fade-in duration-500 overflow-hidden">
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <button onClick={onBack} className="text-gray-400 hover:text-gray-900"><i className="bi bi-arrow-left text-2xl"></i></button>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tighter">Monitoring Meja</h1>
                    </div>
                    <div className="flex gap-2">
                        <span className="bg-blue-100 text-blue-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Cooking</span>
                        <span className="bg-green-100 text-green-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Ready</span>
                        <span className="bg-purple-100 text-purple-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Served</span>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start custom-scrollbar pr-2">
                    {orders.length === 0 && !loading && (
                        <div className="col-span-full h-64 flex flex-col items-center justify-center opacity-20">
                            <i className="bi bi-inbox text-6xl"></i>
                            <p className="font-bold uppercase tracking-widest mt-4">Tidak ada pesanan aktif</p>
                        </div>
                    )}
                    {orders.map(order => (
                        <div key={order.id} className="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 flex flex-col h-fit">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h4 className="font-black text-gray-900 text-xl">Meja {order.table?.name || 'TA'}</h4>
                                    <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{order.customer_name}</p>
                                </div>
                                <span className={`px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest ${order.stage === 'READY' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600'}`}>
                                    {order.stage}
                                </span>
                            </div>

                            <div className="space-y-3 mb-4">
                                {order.items.map((item, idx) => {
                                    const statusColors = {
                                        'cooking': 'bg-blue-100 text-blue-600',
                                        'ready': 'bg-green-100 text-green-600',
                                        'served': 'bg-purple-100 text-purple-600',
                                        'void': 'bg-red-100 text-red-600'
                                    };
                                    return (
                                        <div key={idx} className="p-3 rounded-2xl bg-gray-50 flex flex-col gap-2">
                                            <div className="flex justify-between items-center group">
                                                <span className="text-xs font-bold text-gray-900">{item.qty}x {item.menu_name}</span>
                                                <div className="flex items-center gap-2">
                                                    <span className={`text-[8px] font-black uppercase px-1.5 py-0.5 rounded ${statusColors[item.status] || 'bg-gray-200 text-gray-400'}`}>
                                                        {item.status}
                                                    </span>
                                                    {(item.status === 'pending' || item.status === 'cooking') && (
                                                        <button 
                                                            onClick={() => handleVoidItem(order.id, item.id, item.menu_name)}
                                                            className="w-6 h-6 rounded-full bg-red-50 text-red-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-500 hover:text-white"
                                                        >
                                                            <i className="bi bi-trash"></i>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            {item.status === 'ready' && (
                                                <button 
                                                    onClick={() => setConfirmItem({ orderId: order.id, itemId: item.id, menuName: item.menu_name })}
                                                    className="w-full py-2 bg-green-500 text-white rounded-xl font-black text-[10px] uppercase tracking-widest active:scale-95 transition-all shadow-md shadow-green-500/20"
                                                >
                                                    Mark as Served
                                                </button>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>

                {confirmItem && (
                    <ConfirmModal
                        title="Sajikan Menu?"
                        message={`Konfirmasi bahwa ${confirmItem.menuName} sudah diantarkan ke meja?`}
                        onConfirm={handleItemServe}
                        onClose={() => setConfirmItem(null)}
                        confirmText="Ya, Sajikan"
                    />
                )}
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
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Tipe</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {history.map(order => (
                                <tr key={order.id} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                    <td className="p-6 text-sm font-bold text-gray-600">{new Date(order.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}</td>
                                    <td className="p-6 text-sm font-black text-gray-900">{order.code}</td>
                                    <td className="p-6 text-sm font-bold text-gray-600">{order.table?.name || 'TA'}</td>
                                    <td className="p-6 text-sm font-black text-orange-500">{order.order_type}</td>
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

    const root = ReactDOM.createRoot(document.getElementById('waiter-root'));
    root.render(<WaiterTerminal />);

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
