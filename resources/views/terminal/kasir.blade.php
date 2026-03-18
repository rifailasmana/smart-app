@extends('layouts.terminal')

@section('title', 'Terminal Kasir')
@section('terminal_role', 'KASIR')

@section('content')
<div id="kasir-root" class="w-full h-full"></div>
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
            secondary: 'bg-terminal-bg border border-terminal-border text-terminal-text hover:bg-black/5',
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
    const KasirTerminal = () => {
        const [activeTab, setActiveTab] = useState('waiting'); 
        const [activeOrder, setActiveOrder] = useState(null);
        const [paymentMethod, setPaymentMethod] = useState('cash');
        const [amountPaid, setAmountPaid] = useState(0);
        const [isProcessing, setIsProcessing] = useState(false);
        const [isEditing, setIsEditing] = useState(false);
        const [isSplitting, setIsSplitting] = useState(false);
        const [splitItems, setSplitItems] = useState([]);
        const [couponCode, setCouponCode] = useState('');
        const [appliedCoupon, setAppliedCoupon] = useState(null);
        const [discountPercent, setDiscountPercent] = useState(0);
        const [orders, setOrders] = useState([]);
        const [menuItems] = useState(@json($menuItems));
        const [categories] = useState(['All', ...@json($categories)]);
        const [tables] = useState(@json($tables));
        const [searchMenu, setSearchMenu] = useState('');
        const [activeCategory, setActiveCategory] = useState('All');

        const fetchOrders = useCallback(async () => {
            try {
                const response = await fetch('/terminal/orders?role=kasir');
                const data = await response.json();
                setOrders(data);
                // Update active order if it exists in the new data
                if (activeOrder) {
                    const updated = data.find(o => o.id === activeOrder.id);
                    if (updated) {
                        // Keep current local edits if in editing mode
                        if (!isEditing && !isSplitting) {
                            setActiveOrder(updated);
                        }
                    }
                }
            } catch (e) { console.error('Failed to fetch orders'); }
        }, [activeOrder, isEditing, isSplitting]);

        useEffect(() => {
            fetchOrders();
            const interval = setInterval(fetchOrders, 10000);
            return () => clearInterval(interval);
        }, [fetchOrders]);

        const waitingOrders = useMemo(() => orders.filter(o => o.stage === 'WAITING_CASHIER'), [orders]);
        const paidOrders = useMemo(() => orders.filter(o => ['READY_FOR_KITCHEN', 'COOKING', 'READY', 'DONE'].includes(o.stage)), [orders]);
        const filteredOrders = activeTab === 'waiting' ? waitingOrders : paidOrders;

        const filteredMenu = useMemo(() => {
            return menuItems.filter(m => {
                const matchesSearch = m.name.toLowerCase().includes(searchMenu.toLowerCase());
                const matchesCategory = activeCategory === 'All' || m.category === activeCategory;
                return matchesSearch && matchesCategory;
            });
        }, [menuItems, searchMenu, activeCategory]);

        const handleSelectOrder = (order) => {
            const orderCopy = JSON.parse(JSON.stringify(order));
            setActiveOrder(orderCopy);
            setAmountPaid(orderCopy.total);
            setPaymentMethod('cash');
            setIsEditing(false);
            setIsSplitting(false);
            setSplitItems([]);
            setCouponCode('');
            setAppliedCoupon(null);
            setDiscountPercent(0);
        };

        const handleToggleSplitItem = (item, qty) => {
            setSplitItems(prev => {
                const existing = prev.find(i => i.order_item_id === item.id);
                if (existing) {
                    const newItems = prev.filter(i => i.order_item_id !== item.id);
                    if (qty > 0) newItems.push({ order_item_id: item.id, qty });
                    return newItems;
                }
                return [...prev, { order_item_id: item.id, qty }];
            });
        };

        const handleProcessSplit = async () => {
            if (splitItems.length === 0) return;
            if (splitItems.length === activeOrder.items.length && splitItems.every(si => si.qty === activeOrder.items.find(ai => ai.id === si.order_item_id).qty)) {
                alert('Tidak bisa memindahkan semua item. Gunakan Edit Order jika ingin mengubah seluruh pesanan.');
                return;
            }

            setIsProcessing(true);
            try {
                const response = await fetch(`/terminal/orders/${activeOrder.id}/split`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ items: splitItems })
                });
                if (response.ok) {
                    alert('Pesanan berhasil dipisah!');
                    const result = await response.json();
                    setActiveOrder(result.original_order);
                    setIsSplitting(false);
                    setSplitItems([]);
                    fetchOrders();
                } else alert('Gagal memisahkan pesanan');
            } catch (e) { alert('Error: ' + e.message); }
            finally { setIsProcessing(false); }
        };

        const handleUpdateQty = (index, delta) => {
            const newItems = [...activeOrder.items];
            newItems[index].qty += delta;
            if (newItems[index].qty <= 0) newItems.splice(index, 1);
            recalculate(newItems);
        };

        const handleAddItem = (menu) => {
            const newItems = [...activeOrder.items];
            const existing = newItems.find(i => i.menu_item_id === menu.id);
            if (existing) {
                existing.qty++;
            } else {
                newItems.push({
                    menu_item_id: menu.id,
                    menu_name: menu.name,
                    price: menu.price,
                    qty: 1,
                    note: ''
                });
            }
            recalculate(newItems);
        };

        const recalculate = (items) => {
            const newTotal = items.reduce((sum, i) => sum + (i.price * i.qty), 0);
            setActiveOrder({ ...activeOrder, items, total: newTotal });
            setAmountPaid(newTotal * (1 - discountPercent/100));
        };

        const handleUpdateItemNote = (index, note) => {
            const newItems = [...activeOrder.items];
            newItems[index].note = note;
            setActiveOrder({ ...activeOrder, items: newItems });
        };

        const handleCheckCoupon = async () => {
            if (!couponCode) return;
            try {
                const response = await fetch('/terminal/coupons/check', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ code: couponCode })
                });
                const data = await response.json();
                if (data.error) alert(data.error);
                else {
                    setAppliedCoupon(data.code);
                    setDiscountPercent(data.discount_percent);
                    setAmountPaid(activeOrder.total * (1 - data.discount_percent/100));
                }
            } catch (e) { alert('Gagal mengecek kupon'); }
        };

        const handleProcessPayment = async () => {
            if (!confirm('Selesaikan pembayaran?')) return;
            setIsProcessing(true);
            try {
                const response = await fetch(`/terminal/orders/${activeOrder.id}/approve-and-pay`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        payment_method: paymentMethod,
                        amount_paid: amountPaid,
                        coupon_code: appliedCoupon,
                        discount_percent: discountPercent,
                        items: activeOrder.items.map(i => ({ menu_item_id: i.menu_item_id, qty: i.qty, note: i.note }))
                    })
                });
                if (response.ok) {
                    alert('Pembayaran Berhasil!');
                    const result = await response.json();
                    setActiveOrder(result.order);
                    setIsEditing(false);
                    fetchOrders();
                    if (confirm('Cetak struk?')) printReceipt(result.order);
                } else alert('Gagal memproses pembayaran');
            } catch (e) { alert('Error: ' + e.message); }
            finally { setIsProcessing(false); }
        };

        const printReceipt = (order) => {
            const win = window.open('', '_blank', 'width=400,height=600');
            win.document.write(`
                <html><head><title>Struk #${order.code}</title></head>
                <body style="font-family:monospace;padding:20px;font-size:12px;">
                    <div style="text-align:center;border-bottom:1px dashed #000;margin-bottom:10px;">
                        <h2>{{ $warung->name }}</h2><p>Bukti Pembayaran</p>
                    </div>
                    <p>Meja: ${order.table.name}<br>Kode: ${order.code}<br>Waktu: ${new Date().toLocaleString('id-ID')}</p>
                    <table style="width:100%;border-bottom:1px dashed #000;margin-bottom:10px;">
                        ${order.items.map(i => `<tr><td>${i.qty}x ${i.menu_name}</td><td style="text-align:right;">${formatPrice(i.price * i.qty)}</td></tr>`).join('')}
                    </table>
                    <div style="text-align:right;">
                        <p>Subtotal: ${formatPrice(order.subtotal || order.total)}</p>
                        ${order.discount ? `<p>Diskon: -${formatPrice(order.discount)}</p>` : ''}
                        <h3>TOTAL: ${formatPrice(order.total)}</h3>
                    </div>
                    <div style="text-align:center;margin-top:20px;"><p>Terima Kasih!</p></div>
                    <script>window.print();setTimeout(()=>window.close(),500);<\/script>
                </body></html>
            `);
        };

        const formatPrice = (p) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(p);
        const formatTime = (ts) => new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        const GUEST_COLORS = {
            'REGULER': 'bg-terminal-accent',
            'RESERVED': 'bg-terminal-warning',
            'MAJAR_PRIORITY': 'bg-blue-500',
            'MAJAR_OWNER': 'bg-purple-500'
        };

        return (
            <div className="flex w-full h-full bg-terminal-bg text-terminal-text overflow-hidden font-sans">
                {/* --- Left Panel: Queue (25%) --- */}
                <div className="w-[25%] flex flex-col border-r border-terminal-border bg-terminal-panel shadow-2xl z-10">
                    <div className="flex p-4 gap-2 bg-black/30 border-b border-terminal-border">
                        <button className={`flex-1 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all ${activeTab === 'waiting' ? 'bg-terminal-accent text-black' : 'bg-terminal-bg border border-terminal-border text-terminal-muted'}`} onClick={() => setActiveTab('waiting')}>Menunggu</button>
                        <button className={`flex-1 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all ${activeTab === 'paid' ? 'bg-terminal-accent text-black' : 'bg-terminal-bg border border-terminal-border text-terminal-muted'}`} onClick={() => setActiveTab('paid')}>Selesai</button>
                    </div>
                    <div className="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        {filteredOrders.map(order => {
                            const guestColor = GUEST_COLORS[order.guest_category] || 'bg-terminal-accent';
                            let mergedInfo = '';
                            try {
                                const mergedIds = JSON.parse(order.merged_table_ids || '[]');
                                if (mergedIds.length > 0) {
                                    const names = mergedIds.map(id => {
                                        const t = tables.find(tbl => String(tbl.id) === String(id));
                                        return t ? t.name : id;
                                    });
                                    mergedInfo = `+ ${names.join(', ')}`;
                                }
                            } catch(e) {}

                            return (
                                <div key={order.id} className={`p-4 rounded-2xl border-2 cursor-pointer transition-all active:scale-95 ${activeOrder?.id === order.id ? 'border-terminal-accent bg-terminal-accent/10' : 'border-terminal-border bg-terminal-bg'}`} onClick={() => handleSelectOrder(order)}>
                                    <div className="flex justify-between items-start mb-2">
                                        <div className="text-xl font-black">
                                            Meja {order.table.name}
                                            {mergedInfo && <span className="text-[10px] ml-2 text-terminal-muted">({mergedInfo})</span>}
                                        </div>
                                        <div className="text-terminal-accent font-black">{formatPrice(order.total)}</div>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <Badge color={guestColor}>{order.guest_category || 'REGULER'}</Badge>
                                        <div className="text-terminal-muted text-[10px] font-mono"><span>#{order.code}</span></div>
                                    </div>
                                    <div className="flex justify-between items-center mt-2 text-terminal-muted text-[10px] font-bold uppercase tracking-widest">
                                        <span>{order.order_type || 'DINE_IN'}</span>
                                        <span>{formatTime(order.created_at)}</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* --- Middle Panel: Details/Edit (40%) --- */}
                <div className="flex-1 flex flex-col border-r border-terminal-border bg-terminal-bg">
                    {!activeOrder ? (
                        <div className="flex-1 flex flex-col items-center justify-center text-terminal-muted opacity-20"><i className="bi bi-receipt text-[8rem] mb-6"></i><h2 className="text-2xl font-black uppercase tracking-widest">Pilih Antrian</h2></div>
                    ) : (
                        <>
                            <div className="p-6 bg-terminal-panel border-b border-terminal-border flex justify-between items-center shadow-sm">
                                <div>
                                    <div className="flex items-center gap-3">
                                        <h2 className="text-3xl font-black text-terminal-text">Meja {activeOrder.table.name}</h2>
                                        <Badge color={GUEST_COLORS[activeOrder.guest_category] || 'bg-terminal-accent'}>{activeOrder.guest_category}</Badge>
                                    </div>
                                    <div className="text-terminal-muted text-xs font-mono">#{activeOrder.code} • {activeOrder.order_type}</div>
                                </div>
                                <div className="flex gap-2">
                                    <Button 
                                        className={`h-12 ${isSplitting ? 'bg-terminal-warning text-white' : ''}`} 
                                        icon={isSplitting ? 'bi-check-lg' : 'bi-layers-half'} 
                                        onClick={() => { setIsSplitting(!isSplitting); setIsEditing(false); }}
                                    >
                                        {isSplitting ? 'Selesai Split' : 'Split Bill'}
                                    </Button>
                                    <Button 
                                        className={`h-12 ${isEditing ? 'border-terminal-accent text-terminal-accent' : ''}`} 
                                        icon={isEditing ? 'bi-pencil-square' : 'bi-pencil-square'} 
                                        onClick={() => { setIsEditing(!isEditing); setIsSplitting(false); }}
                                    >
                                        {isEditing ? 'Selesai Edit' : 'Edit Order'}
                                    </Button>
                                </div>
                            </div>
                            <div className="flex-1 overflow-y-auto p-6 custom-scrollbar bg-terminal-bg/30">
                                {isSplitting && (
                                    <div className="bg-terminal-warning/10 border border-terminal-warning/30 rounded-2xl p-4 mb-6 animate-pulse shadow-sm">
                                        <p className="text-terminal-warning font-black text-xs uppercase tracking-widest"><i className="bi bi-info-circle-fill mr-2"></i>Mode Split Bill: Pilih item yang ingin dipindah ke bill baru</p>
                                    </div>
                                )}
                                <table className="w-full border-separate border-spacing-y-2">
                                    <thead><tr className="text-terminal-muted text-[10px] font-black uppercase tracking-widest"><th className="px-4 py-2 text-left">Item</th><th className="px-4 py-2 text-center">Qty</th><th className="px-4 py-2 text-right">Total</th></tr></thead>
                                    <tbody>
                                        {activeOrder.items.map((item, idx) => {
                                            const splitItem = splitItems.find(si => si.order_item_id === item.id);
                                            
                                            return (
                                                <tr key={idx} className={`bg-terminal-panel border border-terminal-border transition-all shadow-sm ${isSplitting && splitItem ? 'border-terminal-warning bg-terminal-warning/5' : ''}`}>
                                                    <td className="px-4 py-3 rounded-l-xl">
                                                        <div className="font-black text-terminal-text">{item.menu_name}</div>
                                                        {isEditing ? <input type="text" className="w-full bg-terminal-bg border border-terminal-border rounded px-2 py-1 text-[10px] mt-2 focus:outline-none text-terminal-text" value={item.note || ''} onChange={e => handleUpdateItemNote(idx, e.target.value)} placeholder="Catatan..." /> : item.note && <div className="text-[10px] text-terminal-warning italic mt-1">{item.note}</div>}
                                                    </td>
                                                    <td className="px-4 py-3 text-center">
                                                        {isSplitting ? (
                                                            <div className="flex items-center justify-center gap-2">
                                                                <button onClick={() => handleToggleSplitItem(item, (splitItem?.qty || 0) - 1)} className="w-8 h-8 rounded bg-terminal-bg border border-terminal-border text-terminal-text hover:bg-black/5">-</button>
                                                                <span className={`font-black min-w-[30px] ${splitItem ? 'text-terminal-warning' : 'text-terminal-text'}`}>{splitItem?.qty || 0} / {item.qty}</span>
                                                                <button onClick={() => (splitItem?.qty || 0) < item.qty && handleToggleSplitItem(item, (splitItem?.qty || 0) + 1)} className="w-8 h-8 rounded bg-terminal-bg border border-terminal-border text-terminal-text hover:bg-black/5">+</button>
                                                            </div>
                                                        ) : !isEditing ? (
                                                            <span className="font-black text-terminal-text">{item.qty}</span>
                                                        ) : (
                                                            <div className="flex items-center justify-center gap-2">
                                                                <button onClick={() => handleUpdateQty(idx, -1)} className="w-8 h-8 rounded bg-terminal-bg border border-terminal-border text-terminal-text hover:bg-black/5">-</button>
                                                                <span className="font-black min-w-[20px] text-terminal-text">{item.qty}</span>
                                                                <button onClick={() => handleUpdateQty(idx, 1)} className="w-8 h-8 rounded bg-terminal-bg border border-terminal-border text-terminal-text hover:bg-black/5">+</button>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-black rounded-r-xl text-terminal-text">{formatPrice(item.price * (isSplitting ? (splitItem?.qty || 0) : item.qty))}</td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                </div>

                {/* --- Right Panel: Payment/Add Menu (35%) --- */}
                <div className="w-[35%] flex flex-col bg-terminal-panel shadow-2xl z-20">
                    {isSplitting ? (
                        <div className="flex-1 flex flex-col p-8 justify-between bg-terminal-panel">
                            <div className="space-y-6">
                                <h3 className="text-2xl font-black uppercase tracking-widest text-terminal-warning">Split Bill</h3>
                                <p className="text-terminal-muted">Item yang dipilih akan dipindahkan ke bill baru untuk meja yang sama.</p>
                                
                                <div className="bg-terminal-bg rounded-2xl p-6 border border-terminal-border shadow-inner">
                                    <div className="text-[10px] font-black text-terminal-muted uppercase mb-4 tracking-widest">Ringkasan Bill Baru</div>
                                    {splitItems.length === 0 ? (
                                        <div className="text-center py-10 text-terminal-muted opacity-30 italic text-sm">Belum ada item dipilih</div>
                                    ) : (
                                        <div className="space-y-2">
                                            {splitItems.map(si => {
                                                const item = activeOrder.items.find(ai => ai.id === si.order_item_id);
                                                return (
                                                    <div key={si.order_item_id} className="flex justify-between text-sm text-terminal-text">
                                                        <span>{si.qty}x {item.menu_name}</span>
                                                        <span className="font-black">{formatPrice(item.price * si.qty)}</span>
                                                    </div>
                                                );
                                            })}
                                            <div className="pt-4 border-t border-terminal-border flex justify-between items-center text-terminal-text">
                                                <span className="font-black">Total Bill Baru</span>
                                                <span className="text-2xl font-black text-terminal-warning">
                                                    {formatPrice(splitItems.reduce((sum, si) => {
                                                        const item = activeOrder.items.find(ai => ai.id === si.order_item_id);
                                                        return sum + (item.price * si.qty);
                                                    }, 0))}
                                                </span>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            <div className="space-y-4">
                                <Button 
                                    variant="warning" 
                                    className="w-full py-5 text-lg shadow-lg shadow-terminal-warning/20" 
                                    disabled={splitItems.length === 0 || isProcessing}
                                    onClick={handleProcessSplit}
                                >
                                    {isProcessing ? 'MEMPROSES...' : 'PISAHKAN KE BILL BARU'}
                                </Button>
                                <Button 
                                    variant="ghost" 
                                    className="w-full" 
                                    onClick={() => { setIsSplitting(false); setSplitItems([]); }}
                                >
                                    BATAL
                                </Button>
                            </div>
                        </div>
                    ) : isEditing ? (
                        <div className="flex-1 flex flex-col overflow-hidden">
                            <div className="p-6 bg-terminal-bg border-b border-terminal-border space-y-4 shadow-sm">
                                <div className="relative">
                                    <i className="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-terminal-muted"></i>
                                    <input type="text" className="w-full bg-white border border-terminal-border rounded-2xl pl-12 pr-4 py-4 text-lg focus:outline-none focus:border-terminal-accent shadow-sm text-terminal-text" placeholder="Cari Menu..." value={searchMenu} onChange={e => setSearchMenu(e.target.value)} />
                                </div>
                                <div className="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                                    {categories.map(cat => (
                                        <button 
                                            key={cat}
                                            onClick={() => setActiveCategory(cat)}
                                            className={`px-4 py-1.5 rounded-full font-black text-[10px] uppercase tracking-widest transition-all whitespace-nowrap ${activeCategory === cat ? 'bg-terminal-accent text-white shadow-lg shadow-terminal-accent/20' : 'bg-white border border-terminal-border text-terminal-muted hover:border-terminal-accent/50'}`}
                                        >
                                            {cat}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="flex-1 overflow-y-auto p-4 grid grid-cols-1 gap-3 custom-scrollbar bg-terminal-bg/10">
                                {filteredMenu.map(menu => (
                                    <div key={menu.id} className="bg-white border border-terminal-border p-4 rounded-2xl cursor-pointer hover:border-terminal-accent transition-all active:scale-95 flex justify-between items-center group shadow-sm hover:shadow-md" onClick={() => handleAddItem(menu)}>
                                        <div className="font-black group-hover:text-terminal-accent text-terminal-text">{menu.name}</div>
                                        <div className="text-terminal-accent font-black">{formatPrice(menu.price)}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : activeOrder ? (
                        <div className="flex-1 flex flex-col p-8 justify-between">
                            <div className="space-y-8">
                                <div>
                                    <h4 className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-4">Metode Bayar</h4>
                                    <div className="grid grid-cols-2 gap-3">
                                        {['cash', 'qris', 'card', 'other'].map(m => (
                                            <button key={m} className={`h-16 rounded-2xl border-2 font-black flex items-center justify-center gap-3 transition-all ${paymentMethod === m ? 'border-terminal-accent bg-terminal-accent/10 text-terminal-accent shadow-sm' : 'border-terminal-border bg-white text-terminal-muted hover:border-terminal-accent/30'}`} onClick={() => setPaymentMethod(m)}><i className={`bi bi-${m === 'cash' ? 'cash-stack' : m === 'qris' ? 'qr-code-scan' : m === 'card' ? 'credit-card' : 'wallet2'} text-xl`}></i>{m.toUpperCase()}</button>
                                        ))}
                                    </div>
                                </div>
                                <div>
                                    <h4 className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-4">Kupon</h4>
                                    <div className="flex gap-2"><input type="text" className="flex-1 bg-white border border-terminal-border rounded-xl px-4 py-2 text-lg focus:outline-none uppercase text-terminal-text shadow-sm" placeholder="KODE" value={couponCode} onChange={e => setCouponCode(e.target.value)} /><Button className="px-4 py-2" onClick={handleCheckCoupon}>Cek</Button></div>
                                    {discountPercent > 0 && <div className="mt-2 text-terminal-accent text-xs font-bold"><i className="bi bi-check-circle-fill mr-1"></i>Kupon {appliedCoupon} diterapkan!</div>}
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div className="border-t border-terminal-border pt-6 space-y-2">
                                    <div className="flex justify-between text-terminal-muted text-xs font-bold"><span>SUBTOTAL</span><span>{formatPrice(activeOrder.total)}</span></div>
                                    {discountPercent > 0 && <div className="flex justify-between text-terminal-danger text-xs font-bold"><span>DISKON</span><span>-{formatPrice(activeOrder.total * (discountPercent/100))}</span></div>}
                                    <div className="flex justify-between items-center text-terminal-text"><span className="text-xl font-black">TOTAL</span><span className="text-4xl font-black text-terminal-accent">{formatPrice(activeOrder.total * (1 - discountPercent/100))}</span></div>
                                </div>
                                {paymentMethod === 'cash' && (
                                    <div className="bg-terminal-bg/50 p-4 rounded-2xl border border-terminal-border space-y-3 shadow-inner">
                                        <div className="flex justify-between items-center text-terminal-text"><span className="text-[10px] font-black text-terminal-muted">BAYAR</span><input type="number" className="bg-white border border-terminal-border rounded-lg px-3 py-1 text-xl font-black text-right w-[140px] focus:outline-none text-terminal-text shadow-sm" value={amountPaid} onChange={e => setAmountPaid(Number(e.target.value))} onClick={e => e.target.select()} /></div>
                                        {amountPaid > (activeOrder.total * (1 - discountPercent/100)) && <div className="flex justify-between items-center text-terminal-warning"><span className="text-[10px] font-black">KEMBALI</span><span className="text-xl font-black">{formatPrice(amountPaid - (activeOrder.total * (1 - discountPercent/100)))}</span></div>}
                                    </div>
                                )}
                                <Button variant="primary" className="w-full py-5 text-xl uppercase tracking-widest shadow-xl shadow-terminal-accent/20" disabled={isProcessing || (paymentMethod === 'cash' && amountPaid < (activeOrder.total * (1 - discountPercent/100)))} onClick={handleProcessPayment}>{isProcessing ? 'MEMPROSES...' : 'BAYAR SEKARANG'}</Button>
                            </div>
                        </div>
                    ) : (
                        <div className="flex-1 flex flex-col items-center justify-center opacity-10 text-terminal-muted"><i className="bi bi-credit-card text-[6rem]"></i></div>
                    )}
                </div>

                <style>{`
                    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
                    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
                    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
                `}</style>
            </div>
        );
    };

    const root = ReactDOM.createRoot(document.getElementById('kasir-root'));
    root.render(<KasirTerminal />);
</script>
@endsection
