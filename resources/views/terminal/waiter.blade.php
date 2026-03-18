@extends('layouts.terminal')

@section('title', 'Terminal Waiter')
@section('terminal_role', 'WAITER')

@section('content')
<div id="waiter-root" class="w-full h-full"></div>
@endsection

@section('extra_js')
<script type="text/babel">
    const { useState, useEffect, useMemo, useCallback } = React;

    // --- Shared Components ---
    const Badge = ({ children, color = 'bg-terminal-border' }) => (
        <span className={`${color} text-black text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase tracking-wider`}>
            {children}
        </span>
    );

    const Button = ({ children, onClick, variant = 'secondary', disabled = false, className = '', icon = null }) => {
        const variants = {
            primary: 'bg-terminal-accent text-white hover:opacity-90 shadow-lg shadow-terminal-accent/20',
            secondary: 'bg-white border border-terminal-border text-terminal-text hover:bg-black/5',
            danger: 'bg-terminal-danger text-white hover:opacity-90 shadow-lg shadow-terminal-danger/20',
            ghost: 'bg-transparent text-terminal-muted hover:text-terminal-text',
            warning: 'bg-terminal-warning text-white hover:opacity-90 shadow-lg shadow-terminal-warning/20'
        };
        return (
            <button 
                onClick={onClick} 
                disabled={disabled}
                className={`flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold transition-all active:scale-95 disabled:opacity-30 disabled:active:scale-100 ${variants[variant]} ${className}`}
            >
                {icon && <i className={`bi ${icon}`}></i>}
                {children}
            </button>
        );
    };

    // --- Main Application ---
    const WaiterTerminal = () => {
        const [activeTab, setActiveTab] = useState('tables'); 
        const [searchTable, setSearchTable] = useState('');
        const [searchMenu, setSearchMenu] = useState('');
        const [activeCategory, setActiveCategory] = useState('All');
        const [activeTable, setActiveTable] = useState(null);
        const [activeOrderId, setActiveOrderId] = useState(null);
        const [orderItems, setOrderItems] = useState([]);
        const [activeOrders, setActiveOrders] = useState([]);
        const [tables, setTables] = useState(@json($tables));
        const [menuItems] = useState(@json($menuItems));
        const [categories] = useState(['All', ...@json($categories)]);
        const [isLoading, setIsLoading] = useState(false);

        // New Guest Info State
        const [guestCategory, setGuestCategory] = useState('REGULER');
        const [orderType, setOrderType] = useState('DINE_IN');
        const [reservationName, setReservationName] = useState('');
        const [reservationCode, setReservationCode] = useState('');
        const [mergedTables, setMergedTables] = useState([]);
        const [showGuestModal, setShowGuestModal] = useState(false);
        const [showMergeModal, setShowMergeModal] = useState(false);
        const [showConfirmModal, setShowConfirmModal] = useState(false);

        const GUEST_CATEGORIES = [
            { id: 'REGULER', label: 'Reguler', color: 'bg-terminal-accent' },
            { id: 'RESERVED', label: 'Reserved', color: 'bg-terminal-warning' },
            { id: 'MAJAR_PRIORITY', label: 'Majar Priority', color: 'bg-blue-500' },
            { id: 'MAJAR_OWNER', label: 'Majar Owner', color: 'bg-purple-500' }
        ];

        const fetchActiveOrders = useCallback(async () => {
            try {
                const response = await fetch('/terminal/orders?role=waiter');
                const data = await response.json();
                setActiveOrders(data);
                setTables(prev => prev.map(t => {
                    const order = data.find(o => o.table_id === t.id && o.stage !== 'DONE');
                    // Check if table is part of a merged order
                    const mergedOrder = data.find(o => {
                        if (!o.merged_table_ids) return false;
                        try {
                            const ids = JSON.parse(o.merged_table_ids);
                            return Array.isArray(ids) && ids.includes(t.id);
                        } catch(e) { return false; }
                    });

                    return {
                        ...t,
                        has_draft: (order && order.stage === 'DRAFT') || (mergedOrder && mergedOrder.stage === 'DRAFT'),
                        current_order: order || mergedOrder,
                        is_merged_child: !!mergedOrder && mergedOrder.table_id !== t.id
                    };
                }));
            } catch (e) { console.error('Failed to fetch orders'); }
        }, []);

        useEffect(() => {
            fetchActiveOrders();
            const interval = setInterval(fetchActiveOrders, 10000);
            return () => clearInterval(interval);
        }, [fetchActiveOrders]);

        const filteredTables = useMemo(() => {
            if (!searchTable) return tables;
            return tables.filter(t => t.name.toLowerCase().includes(searchTable.toLowerCase()));
        }, [tables, searchTable]);

        const filteredMenu = useMemo(() => {
            return menuItems.filter(m => {
                const matchesSearch = m.name.toLowerCase().includes(searchMenu.toLowerCase()) || 
                                    (m.category && m.category.toLowerCase().includes(searchMenu.toLowerCase()));
                const matchesCategory = activeCategory === 'All' || m.category === activeCategory;
                return matchesSearch && matchesCategory;
            });
        }, [menuItems, searchMenu, activeCategory]);

        const orderTotal = useMemo(() => orderItems.reduce((sum, item) => sum + (item.price * item.qty), 0), [orderItems]);

        const handleSelectTable = async (table) => {
            if (table.status === 'occupied' && !table.has_draft && !table.current_order) {
                alert('Meja ini sedang digunakan.');
                return;
            }

            // If it's a child of a merged order, select the parent table/order
            if (table.is_merged_child && table.current_order) {
                const parentTable = tables.find(t => t.id === table.current_order.table_id);
                if (parentTable) {
                    handleSelectTable(parentTable);
                    return;
                }
            }

            setActiveTable(table);
            setOrderItems([]);
            setActiveOrderId(null);
            setGuestCategory('REGULER');
            setOrderType('DINE_IN');
            setReservationName('');
            setReservationCode('');
            setMergedTables([]);
            
            setIsLoading(true);
            try {
                const response = await fetch(`/terminal/tables/${table.id}/draft`);
                const draft = await response.json();
                if (draft) {
                    setActiveOrderId(draft.id);
                    setGuestCategory(draft.guest_category || 'REGULER');
                    setOrderType(draft.order_type || 'DINE_IN');
                    setReservationName(draft.reservation_name || '');
                    setReservationCode(draft.reservation_code || '');
                    try {
                        setMergedTables(JSON.parse(draft.merged_table_ids || '[]'));
                    } catch(e) { setMergedTables([]); }
                    
                    setOrderItems(draft.items.map(i => ({
                        id: i.menu_item_id,
                        name: i.menu_name,
                        price: i.price,
                        qty: i.qty,
                        note: i.note || ''
                    })));
                    setShowGuestModal(false);
                } else {
                    setShowGuestModal(true);
                }
            } catch (e) { 
                console.error('Failed to load draft'); 
                setShowGuestModal(true);
            }
            finally { setIsLoading(false); }
        };

        const handleToggleMergeTable = (tableId) => {
            setMergedTables(prev => {
                if (prev.includes(tableId)) return prev.filter(id => id !== tableId);
                return [...prev, tableId];
            });
        };

        const handleSaveDraft = async () => {
            if (orderItems.length === 0) return;
            setIsLoading(true);
            try {
                const response = await fetch('/terminal/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        order_id: activeOrderId,
                        table_id: activeTable.id,
                        guest_category: guestCategory,
                        order_type: orderType,
                        reservation_name: reservationName,
                        reservation_code: reservationCode,
                        merged_table_ids: JSON.stringify(mergedTables),
                        items: orderItems.map(i => ({ menu_item_id: i.id, qty: i.qty, note: i.note }))
                    })
                });
                
                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Gagal menyimpan');
                }

                const order = await response.json();
                setActiveOrderId(order.id);
                alert('Draft berhasil disimpan!');
                fetchActiveOrders();
            } catch (e) { alert('Gagal: ' + e.message); }
            finally { setIsLoading(false); }
        };

        const handleSubmitToCashier = async () => {
            if (orderItems.length === 0) {
                alert('Pesanan kosong!');
                return;
            }
            setShowConfirmModal(true);
        };

        const confirmSubmitToCashier = async () => {
            setShowConfirmModal(false);
            setIsLoading(true);
            try {
                // 1. Save first to ensure latest items are stored
                const saveResponse = await fetch('/terminal/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        order_id: activeOrderId,
                        table_id: activeTable.id,
                        guest_category: guestCategory,
                        order_type: orderType,
                        reservation_name: reservationName,
                        reservation_code: reservationCode,
                        merged_table_ids: JSON.stringify(mergedTables),
                        items: orderItems.map(i => ({ menu_item_id: i.id, qty: i.qty, note: i.note }))
                    })
                });
                
                if (!saveResponse.ok) {
                    const err = await saveResponse.json();
                    throw new Error(err.error || 'Gagal menyimpan pesanan sebelum dikirim');
                }

                const order = await saveResponse.json();
                
                // 2. Submit to cashier
                const submitRes = await fetch(`/terminal/orders/${order.id}/submit-to-cashier`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    }
                });
                
                if (submitRes.ok) {
                    alert('Pesanan terkirim ke kasir!');
                    setActiveTable(null); 
                    setOrderItems([]); 
                    setActiveOrderId(null);
                    setMergedTables([]);
                    fetchActiveOrders();
                } else {
                    const err = await submitRes.json();
                    throw new Error(err.error || 'Gagal mengirim ke kasir');
                }
            } catch (e) { alert('Gagal: ' + e.message); }
            finally { setIsLoading(false); }
        };

        const handleAddItem = (menu) => {
            setOrderItems(prev => {
                const existing = prev.find(i => i.id === menu.id);
                if (existing) return prev.map(i => i.id === menu.id ? { ...i, qty: i.qty + 1 } : i);
                return [...prev, { id: menu.id, name: menu.name, price: menu.price, qty: 1, note: '' }];
            });
        };

        const handleUpdateQty = (index, delta) => {
            setOrderItems(prev => {
                const newItems = [...prev];
                newItems[index].qty += delta;
                if (newItems[index].qty <= 0) newItems.splice(index, 1);
                return newItems;
            });
        };

        const handleUpdateNote = (index, note) => {
            setOrderItems(prev => {
                const newItems = [...prev];
                newItems[index].note = note;
                return newItems;
            });
        };

        const formatPrice = (p) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(p);
        const formatTime = (ts) => new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        return (
            <div className="flex w-full h-full bg-terminal-bg text-terminal-text overflow-hidden font-sans">
                {/* --- Left Panel: Tables/Status (35%) --- */}
                <div className="w-[35%] flex flex-col border-r border-terminal-border bg-terminal-panel shadow-2xl z-10">
                    <div className="flex p-4 gap-2 bg-black/30 border-b border-terminal-border">
                        <button 
                            className={`flex-1 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all ${activeTab === 'tables' ? 'bg-terminal-accent text-black shadow-lg shadow-terminal-accent/20' : 'bg-terminal-bg border border-terminal-border text-terminal-muted hover:text-terminal-text'}`}
                            onClick={() => setActiveTab('tables')}>Meja</button>
                        <button 
                            className={`flex-1 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all ${activeTab === 'status' ? 'bg-terminal-accent text-black shadow-lg shadow-terminal-accent/20' : 'bg-terminal-bg border border-terminal-border text-terminal-muted hover:text-terminal-text'}`}
                            onClick={() => setActiveTab('status')}>Status</button>
                    </div>

                    <div className="flex-1 overflow-hidden flex flex-col">
                        {activeTab === 'tables' ? (
                            <>
                                <div className="p-4 border-b border-terminal-border/50">
                                    <div className="relative">
                                        <i className="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-terminal-muted"></i>
                                        <input type="text" className="w-full bg-terminal-bg border border-terminal-border rounded-2xl pl-12 pr-4 py-4 text-lg focus:outline-none focus:border-terminal-accent shadow-inner" placeholder="Cari nomor meja..." value={searchTable} onChange={e => setSearchTable(e.target.value)} />
                                    </div>
                                </div>
                                <div className="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-4 content-start custom-scrollbar">
                                    {filteredTables.map(table => (
                                        <div key={table.id} className={`aspect-square rounded-[2rem] border-2 flex flex-col items-center justify-center cursor-pointer transition-all relative group active:scale-95 shadow-sm ${activeTable?.id === table.id ? 'border-terminal-accent bg-terminal-accent/10 shadow-[0_0_30px_rgba(255,140,0,0.15)]' : table.is_merged_child ? 'border-terminal-muted bg-terminal-bg opacity-60' : table.has_draft ? 'border-terminal-warning bg-terminal-warning/5' : table.status === 'occupied' ? 'border-terminal-danger bg-terminal-danger/5 shadow-[0_0_20px_rgba(239,68,68,0.1)]' : 'border-terminal-border bg-white hover:border-terminal-accent/50 hover:shadow-md'}`} onClick={() => handleSelectTable(table)}>
                                            {table.has_draft && <div className="absolute top-4 right-4"><Badge color="bg-terminal-warning">DRAFT</Badge></div>}
                                            {table.is_merged_child && <div className="absolute top-4 right-4"><Badge color="bg-terminal-muted">MERGED</Badge></div>}
                                            <div className="text-4xl font-black mb-1 group-hover:scale-110 transition-transform text-terminal-text">{table.name}</div>
                                            <div className="text-terminal-muted text-xs font-bold uppercase tracking-widest">{table.seats} Kursi</div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                                {activeOrders.map(order => (
                                    <div key={order.id} className="bg-white border border-terminal-border rounded-[1.5rem] p-5 shadow-sm hover:border-terminal-accent/30 transition-colors">
                                        <div className="flex justify-between items-center mb-3">
                                            <div className="font-black text-xl text-terminal-text">Meja {order.table.name}</div>
                                            <Badge color={order.stage === 'WAITING_CASHIER' ? 'bg-terminal-warning' : ['CASHIER_APPROVED', 'READY_FOR_KITCHEN'].includes(order.stage) ? 'bg-blue-500' : order.stage === 'COOKING' ? 'bg-orange-500' : order.stage === 'READY' ? 'bg-terminal-accent' : 'bg-terminal-muted'}>{order.stage}</Badge>
                                        </div>
                                        <div className="flex justify-between items-center mb-2">
                                            <div className="text-[10px] font-black uppercase tracking-widest text-terminal-muted">{order.guest_category || 'REGULER'} • {order.order_type || 'DINE_IN'}</div>
                                            <div className="text-terminal-accent font-black">{formatPrice(order.total)}</div>
                                        </div>
                                        <div className="flex justify-between text-[10px] font-mono text-terminal-muted"><span>#{order.code}</span><span>{formatTime(order.created_at)}</span></div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* --- Middle Panel: Menu Selection (40%) --- */}
                <div className="flex-1 flex flex-col border-r border-terminal-border bg-terminal-bg relative">
                    <div className="p-6 bg-terminal-panel border-b border-terminal-border space-y-4 shadow-sm">
                        <div className="relative">
                            <i className="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-terminal-muted"></i>
                            <input type="text" className="w-full bg-terminal-bg border border-terminal-border rounded-2xl pl-12 pr-4 py-4 text-lg focus:outline-none focus:border-terminal-accent shadow-sm" placeholder="Cari Menu..." value={searchMenu} onChange={e => setSearchMenu(e.target.value)} />
                        </div>
                        <div className="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                            {categories.map(cat => (
                                <button 
                                    key={cat}
                                    onClick={() => setActiveCategory(cat)}
                                    className={`px-6 py-2 rounded-full font-black text-xs uppercase tracking-widest transition-all whitespace-nowrap ${activeCategory === cat ? 'bg-terminal-accent text-white shadow-lg shadow-terminal-accent/20' : 'bg-terminal-panel border border-terminal-border text-terminal-muted hover:border-terminal-accent/50'}`}
                                >
                                    {cat}
                                </button>
                            ))}
                        </div>
                    </div>
                    
                    <div className="flex-1 overflow-y-auto p-6 grid grid-cols-2 gap-4 content-start custom-scrollbar">
                        {filteredMenu.map(menu => (
                            <div key={menu.id} className="bg-terminal-panel border border-terminal-border p-5 rounded-[1.5rem] cursor-pointer hover:border-terminal-accent hover:bg-terminal-accent/5 transition-all active:scale-95 group flex flex-col gap-3 shadow-sm hover:shadow-md" onClick={() => handleAddItem(menu)}>
                                <div className="text-xl font-black group-hover:text-terminal-accent transition-colors leading-tight h-12 overflow-hidden text-terminal-text">{menu.name}</div>
                                <div className="flex items-center justify-between mt-auto">
                                    <div className="text-terminal-accent font-black text-lg">{formatPrice(menu.price)}</div>
                                    <div className="w-8 h-8 rounded-full bg-terminal-bg flex items-center justify-center border border-terminal-border group-hover:bg-terminal-accent group-hover:text-white transition-all"><i className="bi bi-plus-lg"></i></div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {!activeTable && (
                        <div className="absolute inset-0 bg-white/40 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center text-center p-8">
                            <div className="bg-terminal-panel border border-terminal-border p-10 rounded-[3rem] shadow-2xl scale-110">
                                <i className="bi bi-arrow-left-circle text-terminal-accent text-6xl mb-6 block animate-bounce-x"></i>
                                <h2 className="text-3xl font-black uppercase tracking-widest text-terminal-text mb-2">Pilih Meja Dahulu</h2>
                                <p className="text-terminal-muted font-bold">Silakan pilih meja di panel kiri untuk mulai memesan</p>
                            </div>
                        </div>
                    )}
                </div>

                {/* --- Right Panel: Cart (25%) --- */}
                <div className="w-[25%] flex flex-col bg-terminal-panel shadow-[-20px_0_50px_rgba(0,0,0,0.05)] z-20">
                    <div className="p-6 border-b border-terminal-border bg-terminal-bg/50">
                        <h2 className="text-2xl font-black uppercase tracking-widest flex justify-between items-center text-terminal-text">Pesanan {activeTable ? <span className="text-terminal-accent">#{activeTable.name}</span> : ''}</h2>
                    </div>
                    
                    <div className="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        {orderItems.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-20 opacity-20 text-terminal-muted"><i className="bi bi-cart text-5xl mb-4"></i><p className="font-bold">Kosong</p></div>
                        ) : (
                            orderItems.map((item, idx) => (
                                <div key={idx} className="bg-white border border-terminal-border rounded-2xl p-4 space-y-3 shadow-sm">
                                    <div className="flex justify-between items-start">
                                        <div className="font-bold leading-tight flex-1 pr-2 text-terminal-text">{item.name}</div>
                                        <button onClick={() => handleUpdateQty(idx, -item.qty)} className="text-terminal-danger opacity-50 hover:opacity-100"><i className="bi bi-x-circle-fill"></i></button>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2 bg-terminal-bg p-1 rounded-lg border border-terminal-border">
                                            <button onClick={() => handleUpdateQty(idx, -1)} className="w-8 h-8 rounded flex items-center justify-center hover:bg-black/5 text-terminal-text">-</button>
                                            <span className="font-black min-w-[20px] text-center text-terminal-text">{item.qty}</span>
                                            <button onClick={() => handleUpdateQty(idx, 1)} className="w-8 h-8 rounded flex items-center justify-center hover:bg-black/5 text-terminal-text">+</button>
                                        </div>
                                        <div className="font-black text-terminal-accent">{formatPrice(item.price * item.qty)}</div>
                                    </div>
                                    <input type="text" className="w-full bg-terminal-bg border border-terminal-border rounded-lg px-2 py-1 text-[10px] text-terminal-muted focus:text-terminal-text focus:outline-none" placeholder="Catatan..." value={item.note} onChange={e => handleUpdateNote(idx, e.target.value)} />
                                </div>
                            ))
                        )}
                    </div>

                    <div className="p-6 border-t border-terminal-border bg-terminal-bg/50">
                        <div className="flex justify-between items-center mb-6">
                            <div className="text-xs font-black text-terminal-muted uppercase tracking-widest">Total</div>
                            <div className="text-3xl font-black text-terminal-text">{formatPrice(orderTotal)}</div>
                        </div>
                        <div className="flex flex-col gap-3">
                            <Button variant="secondary" className="w-full py-4 text-sm" icon="bi-save" onClick={handleSaveDraft} disabled={isLoading || !activeTable || orderItems.length === 0}>SIMPAN</Button>
                            <Button variant="primary" className="w-full py-4 text-sm" icon="bi-send-check" onClick={handleSubmitToCashier} disabled={isLoading || !activeTable || orderItems.length === 0}>KIRIM KE KASIR</Button>
                        </div>
                    </div>
                </div>

                <style>{`
                    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
                    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
                    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
                    @keyframes bounce-x {
                        0%, 100% { transform: translateX(0); }
                        50% { transform: translateX(-10px); }
                    }
                    .animate-bounce-x { animation: bounce-x 1s infinite; }
                `}</style>

                {/* --- Guest Category Modal --- */}
                {showGuestModal && (
                    <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] flex items-center justify-center p-4">
                        <div className="bg-terminal-panel border border-terminal-border rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
                            <div className="p-8 border-b border-terminal-border bg-black/20 flex justify-between items-center">
                                <h3 className="text-2xl font-black uppercase tracking-widest">Informasi Tamu — <span className="text-terminal-accent">Meja {activeTable.name}</span></h3>
                                <button onClick={() => { setShowGuestModal(false); setActiveTable(null); }} className="text-terminal-muted hover:text-white"><i className="bi bi-x-lg text-2xl"></i></button>
                            </div>
                            
                                <div className="p-8 space-y-8 bg-white">
                                    <div>
                                        <label className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-4 block">Kategori Tamu</label>
                                        <div className="grid grid-cols-2 gap-4">
                                            {GUEST_CATEGORIES.map(cat => (
                                                <button 
                                                    key={cat.id} 
                                                    onClick={() => setGuestCategory(cat.id)}
                                                    className={`p-6 rounded-2xl border-2 flex flex-col items-start gap-2 transition-all ${guestCategory === cat.id ? `border-terminal-accent ${cat.color} text-white shadow-xl` : 'border-terminal-border bg-terminal-bg text-terminal-muted hover:border-terminal-accent/30'}`}
                                                >
                                                    <span className="font-black text-lg uppercase tracking-wider">{cat.label}</span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-8">
                                        <div>
                                            <label className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-4 block">Tipe Pesanan</label>
                                            <div className="flex gap-3">
                                                <button onClick={() => setOrderType('DINE_IN')} className={`flex-1 py-4 rounded-xl font-black border-2 transition-all ${orderType === 'DINE_IN' ? 'bg-terminal-accent border-terminal-accent text-white shadow-md' : 'bg-terminal-bg border-terminal-border text-terminal-muted'}`}>DINE IN</button>
                                                <button onClick={() => setOrderType('TAKE_AWAY')} className={`flex-1 py-4 rounded-xl font-black border-2 transition-all ${orderType === 'TAKE_AWAY' ? 'bg-terminal-danger border-terminal-danger text-white shadow-md' : 'bg-terminal-bg border-terminal-border text-terminal-muted'}`}>TAKE AWAY</button>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-4 block">Opsi Tambahan</label>
                                            <Button 
                                                variant={mergedTables.length > 0 ? 'primary' : 'secondary'} 
                                                className="w-full py-4 text-sm shadow-sm" 
                                                icon="bi-layers-half" 
                                                onClick={() => setShowMergeModal(true)}
                                            >
                                                {mergedTables.length > 0 ? `${mergedTables.length} Meja Digabung` : 'Gabung Meja'}
                                            </Button>
                                        </div>
                                    </div>

                                    {guestCategory === 'RESERVED' && (
                                        <div className="space-y-4 animate-in slide-in-from-right duration-300">
                                            <label className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] block">Detail Reservasi</label>
                                            <input type="text" placeholder="Nama Pemesan" className="w-full bg-terminal-bg border border-terminal-border rounded-xl px-4 py-3 focus:outline-none focus:border-terminal-accent text-terminal-text shadow-sm" value={reservationName} onChange={e => setReservationName(e.target.value)} />
                                            <input type="text" placeholder="Kode / ID Unik" className="w-full bg-terminal-bg border border-terminal-border rounded-xl px-4 py-3 focus:outline-none focus:border-terminal-accent text-terminal-text shadow-sm" value={reservationCode} onChange={e => setReservationCode(e.target.value)} />
                                        </div>
                                    )}
                                </div>

                            <div className="p-8 bg-terminal-bg border-t border-terminal-border">
                                <Button 
                                    variant="primary" 
                                    className="w-full py-5 text-xl shadow-lg shadow-terminal-accent/20" 
                                    disabled={guestCategory === 'RESERVED' && (!reservationName || !reservationCode)}
                                    onClick={() => setShowGuestModal(false)}
                                >
                                    LANJUT KE MENU
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {/* --- Merge Table Modal --- */}
                {showMergeModal && (
                    <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-[110] flex items-center justify-center p-4">
                        <div className="bg-terminal-panel border border-terminal-border rounded-[2.5rem] w-full max-w-3xl overflow-hidden shadow-2xl">
                            <div className="p-8 border-b border-terminal-border bg-black/20 flex justify-between items-center">
                                <h3 className="text-2xl font-black uppercase tracking-widest">Gabung Meja</h3>
                                <Button variant="secondary" className="px-4 py-2" onClick={() => setShowMergeModal(false)}>SELESAI</Button>
                            </div>
                            <div className="p-8 space-y-4">
                                <p className="text-terminal-muted font-bold mb-4">Pilih meja tambahan yang ingin digabung dengan <span className="text-terminal-accent">Meja {activeTable.name}</span></p>
                                <div className="grid grid-cols-4 gap-4 overflow-y-auto max-h-[400px] p-2 custom-scrollbar">
                                    {tables.filter(t => t.id !== activeTable.id).map(table => {
                                        const isSelected = mergedTables.includes(table.id);
                                        const isOccupied = table.status === 'occupied' && !isSelected;
                                        return (
                                            <div 
                                                key={table.id} 
                                                onClick={() => !isOccupied && handleToggleMergeTable(table.id)}
                                                className={`aspect-square rounded-2xl border-2 flex flex-col items-center justify-center cursor-pointer transition-all ${isSelected ? 'border-terminal-accent bg-terminal-accent text-white' : isOccupied ? 'border-terminal-danger/30 bg-terminal-danger/5 opacity-30 cursor-not-allowed' : 'border-terminal-border bg-terminal-bg text-terminal-muted hover:border-terminal-accent/50'}`}
                                            >
                                                <div className="text-2xl font-black">{table.name}</div>
                                                <div className="text-[10px] font-bold uppercase tracking-widest">{table.seats} Kursi</div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                            <div className="p-8 bg-black/20 border-t border-terminal-border flex justify-between items-center">
                                <div className="text-terminal-muted font-bold">Total: <span className="text-white">{mergedTables.length + 1} Meja</span></div>
                                <Button variant="primary" className="px-10" onClick={() => setShowMergeModal(false)}>KONFIRMASI</Button>
                            </div>
                        </div>
                    </div>
                )}

                {/* --- Confirmation Modal --- */}
                {showConfirmModal && (
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-md z-[200] flex items-center justify-center p-4">
                        <div className="bg-terminal-panel border border-terminal-border rounded-[3rem] w-full max-w-xl overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                            <div className="p-10 text-center space-y-6">
                                <div className="w-24 h-24 bg-terminal-accent/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i className="bi bi-send-check text-terminal-accent text-5xl"></i>
                                </div>
                                <h3 className="text-3xl font-black uppercase tracking-widest text-terminal-text">Validasi Pesanan</h3>
                                <p className="text-terminal-muted text-lg">Pastikan semua pesanan sudah benar untuk menghindari <b>VOID</b> atau <b>REFUND</b>.</p>
                                
                                <div className="bg-terminal-bg rounded-2xl p-6 text-left space-y-2 border border-terminal-border shadow-inner">
                                    <div className="flex justify-between text-terminal-text"><span>Meja:</span><span className="font-black text-terminal-accent">#{activeTable.name}</span></div>
                                    <div className="flex justify-between text-terminal-text"><span>Kategori:</span><span className="font-black">{guestCategory}</span></div>
                                    <div className="flex justify-between text-terminal-text"><span>Tipe:</span><span className="font-black">{orderType.replace('_', ' ')}</span></div>
                                    <div className="flex justify-between text-xl mt-4 pt-4 border-t border-terminal-border text-terminal-text"><span>Total:</span><span className="font-black text-terminal-accent">{formatPrice(orderTotal)}</span></div>
                                </div>
                            </div>
                            
                            <div className="p-10 flex gap-4 bg-terminal-bg/50 border-t border-terminal-border">
                                <Button variant="secondary" className="flex-1 py-5" onClick={() => setShowConfirmModal(false)}>KOREKSI</Button>
                                <Button variant="primary" className="flex-1 py-5 shadow-lg shadow-terminal-accent/20" onClick={confirmSubmitToCashier}>KIRIM SEKARANG</Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    const root = ReactDOM.createRoot(document.getElementById('waiter-root'));
    root.render(<WaiterTerminal />);
</script>
@endsection
