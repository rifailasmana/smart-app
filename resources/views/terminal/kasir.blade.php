@extends('layouts.terminal')

@section('title', 'POS Command Center')
@section('terminal_role', 'KASIR COMMAND CENTER')

@section('header_extra')
<div class="d-flex align-items-center gap-3 border-start ps-3 border-terminal-border">
    <div id="printer-status" class="d-flex align-items-center gap-2">
        <div class="status-dot w-2 h-2 rounded-full bg-terminal-accent"></div>
        <span class="text-[10px] font-black uppercase tracking-widest text-terminal-muted">Printer: Online</span>
    </div>
</div>
@endsection

@section('content')
<div id="kasir-root" class="w-full h-full flex bg-terminal-bg"></div>
@endsection

@section('extra_js')
<script type="text/babel">
    const { useState, useEffect, useMemo, useCallback } = React;

    // --- Shared Components ---
    const Badge = ({ children, color = 'bg-terminal-border' }) => (
        <span className={`${color} text-white text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase tracking-wider`}>
            {children}
        </span>
    );

    const Button = ({ children, onClick, variant = 'secondary', disabled = false, className = '', icon = null, size = 'md' }) => {
        const variants = {
            primary: 'bg-terminal-accent text-white hover:opacity-90 shadow-lg shadow-terminal-accent/20',
            secondary: 'bg-white border border-terminal-border text-terminal-text hover:bg-gray-50',
            danger: 'bg-terminal-danger text-white hover:opacity-90 shadow-lg shadow-terminal-danger/20',
            ghost: 'bg-transparent text-terminal-muted hover:text-terminal-text',
            warning: 'bg-terminal-warning text-white hover:opacity-90 shadow-lg shadow-terminal-warning/20',
            dark: 'bg-terminal-text text-white hover:opacity-90'
        };
        const sizes = {
            sm: 'px-3 py-1.5 text-xs rounded-lg',
            md: 'px-6 py-3 rounded-xl',
            lg: 'px-8 py-4 text-lg rounded-2xl'
        };
        return (
            <button 
                onClick={onClick} 
                disabled={disabled}
                className={`flex items-center justify-center gap-2 font-bold transition-all active:scale-95 disabled:opacity-30 disabled:active:scale-100 ${variants[variant]} ${sizes[size]} ${className}`}
            >
                {icon && <i className={`bi ${icon}`}></i>}
                {children}
            </button>
        );
    };

    const SidebarItem = ({ id, label, icon, active, onClick, count = 0 }) => (
        <div 
            onClick={() => onClick(id)}
            className={`flex flex-col items-center justify-center gap-2 py-6 cursor-pointer transition-all border-l-4 relative ${active ? 'bg-orange-500/10 border-orange-500 text-orange-500 font-black' : 'border-transparent text-terminal-muted hover:text-terminal-text hover:bg-gray-50'}`}
        >
            <i className={`bi ${icon} text-3xl`}></i>
            <span className="text-[10px] uppercase tracking-widest text-center px-2">{label}</span>
            {count > 0 && (
                <div className="absolute top-4 right-4 bg-orange-500 text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center animate-pulse shadow-lg">
                    {count}
                </div>
            )}
        </div>
    );

    // --- Tab Views ---

    const OrderingView = ({ menuItems, categories, tables, onAddItem }) => {
        const [activeCategory, setActiveCategory] = useState('All');
        const [searchQuery, setSearchQuery] = useState('');

        const filteredMenu = useMemo(() => {
            return menuItems.filter(m => {
                const matchesCategory = activeCategory === 'All' || m.category === activeCategory;
                const matchesSearch = m.name.toLowerCase().includes(searchQuery.toLowerCase());
                return matchesCategory && matchesSearch;
            });
        }, [menuItems, activeCategory, searchQuery]);

        const placeholderImg = "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=200&h=200&auto=format&fit=crop";

        return (
            <div className="flex-1 flex overflow-hidden animate-in fade-in duration-500">
                {/* Left: Categories */}
                <div className="w-32 bg-white border-r border-terminal-border flex flex-col overflow-y-auto custom-scrollbar">
                    <div 
                        onClick={() => setActiveCategory('All')}
                        className={`p-4 text-center cursor-pointer transition-all border-b border-terminal-border ${activeCategory === 'All' ? 'bg-terminal-accent text-white font-black' : 'text-terminal-muted font-bold hover:bg-gray-50'}`}
                    >
                        ALL
                    </div>
                    {categories.filter(c => c !== 'All').map(cat => (
                        <div 
                            key={cat}
                            onClick={() => setActiveCategory(cat)}
                            className={`p-4 text-center cursor-pointer transition-all border-b border-terminal-border text-xs uppercase tracking-wider ${activeCategory === cat ? 'bg-terminal-accent text-white font-black' : 'text-terminal-muted font-bold hover:bg-gray-50'}`}
                        >
                            {cat}
                        </div>
                    ))}
                </div>

                {/* Middle: Grid */}
                <div className="flex-1 flex flex-col bg-terminal-bg/30">
                    <div className="p-6 bg-white border-b border-terminal-border shadow-sm">
                        <div className="relative">
                            <i className="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-terminal-muted"></i>
                            <input 
                                type="text" 
                                className="w-full bg-terminal-bg border border-terminal-border rounded-2xl pl-12 pr-4 py-4 text-lg focus:outline-none focus:border-terminal-accent shadow-sm" 
                                placeholder="Cari menu atau meja..." 
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="flex-1 overflow-y-auto p-6 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 content-start custom-scrollbar">
                        {filteredMenu.map(menu => (
                            <div 
                                key={menu.id} 
                                onClick={() => onAddItem(menu)}
                                className="bg-white border border-terminal-border p-4 rounded-3xl cursor-pointer hover:border-terminal-accent hover:shadow-xl transition-all active:scale-95 group flex flex-col gap-3 shadow-sm relative overflow-hidden"
                            >
                                <div className="aspect-square bg-gray-100 rounded-2xl mb-2 overflow-hidden relative">
                                    <img 
                                        src={menu.image || placeholderImg} 
                                        alt={menu.name} 
                                        className="w-full h-full object-cover transition-transform group-hover:scale-110 duration-500" 
                                    />
                                    {menu.stock <= 5 && (
                                        <div className="absolute top-2 right-2 bg-terminal-danger text-white text-[8px] font-black px-2 py-1 rounded-full shadow-lg">
                                            STOK: {menu.stock}
                                        </div>
                                    )}
                                </div>
                                <div className="font-black text-terminal-text leading-tight h-10 overflow-hidden group-hover:text-terminal-accent transition-colors">{menu.name}</div>
                                <div className="text-terminal-accent font-black text-lg">
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(menu.price)}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    };

    const PendingApprovalView = ({ orders, onSelect }) => (
        <div className="flex-1 flex flex-col p-8 bg-terminal-bg/30 overflow-y-auto custom-scrollbar">
            <div className="mb-8 flex justify-between items-center">
                <h2 className="text-3xl font-black uppercase tracking-tighter">Pending Approval <span className="text-terminal-accent ml-2">({orders.length})</span></h2>
                <div className="flex gap-2">
                    <Button variant="secondary" icon="bi-arrow-clockwise">Refresh</Button>
                </div>
            </div>
            {orders.length === 0 ? (
                <div className="flex-1 flex flex-col items-center justify-center opacity-20">
                    <i className="bi bi-clock-history text-[8rem] mb-6"></i>
                    <h3 className="text-2xl font-black uppercase">Belum ada antrean dari Waiter</h3>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    {orders.map(order => (
                        <div 
                            key={order.id} 
                            className="bg-white border-2 border-orange-100 rounded-[2.5rem] p-6 shadow-sm hover:shadow-xl transition-all cursor-pointer hover:border-orange-500 group relative overflow-hidden"
                            onClick={() => onSelect(order)}
                        >
                            <div className="flex justify-between items-start mb-4 relative z-10">
                                <div>
                                    <div className="text-2xl font-black group-hover:text-orange-600 transition-colors">Meja {order.table.name}</div>
                                    <div className="text-terminal-muted text-[10px] font-mono mt-1 uppercase tracking-widest">#{order.code}</div>
                                </div>
                                <div className="text-right">
                                    <Badge color="bg-orange-500 text-white shadow-md">PENDING</Badge>
                                    <div className="text-[10px] font-bold text-terminal-muted mt-2 uppercase tracking-widest">{new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            </div>
                            
                            <div className="space-y-2 mb-6 relative z-10">
                                <div className="flex justify-between text-sm">
                                    <span className="text-terminal-muted font-bold uppercase tracking-wider text-[10px]">Tamu</span>
                                    <span className="font-black text-terminal-text uppercase">{order.customer_name || 'Guest'}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-terminal-muted font-bold uppercase tracking-wider text-[10px]">Kategori</span>
                                    <span className="font-black text-orange-600 uppercase">{order.guest_category}</span>
                                </div>
                            </div>

                            <div className="flex justify-between items-center pt-4 border-t border-orange-50 relative z-10">
                                <div className="text-orange-600 font-black text-xl tracking-tighter">
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(order.total)}
                                </div>
                                <Button size="sm" variant="primary" className="bg-orange-500 border-none px-6">REVIEW</Button>
                            </div>

                            {/* Decorative icon */}
                            <i className="bi bi-receipt absolute -right-4 -bottom-4 text-6xl opacity-[0.03] group-hover:scale-110 group-hover:rotate-12 transition-transform"></i>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );

    const Badge = ({ children, color = 'bg-gray-100' }) => (
        <span className={`${color} text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider shadow-sm`}>
            {children}
        </span>
    );

    const TransactionHistoryView = ({ history, onVoid, onSelect }) => (
        <div className="flex-1 flex flex-col p-8 bg-terminal-bg/30 overflow-hidden">
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h2 className="text-3xl font-black uppercase tracking-tighter text-terminal-text">Riwayat Transaksi</h2>
                    <p className="text-terminal-muted font-bold text-sm">Data mentah untuk audit operasional hari ini.</p>
                </div>
                <div className="flex gap-3">
                    <Button variant="secondary" icon="bi-file-earmark-spreadsheet">Export CSV</Button>
                    <Button variant="secondary" icon="bi-file-pdf">Print Report</Button>
                </div>
            </div>

            <div className="bg-white border border-terminal-border rounded-[2.5rem] overflow-hidden shadow-sm flex-1 flex flex-col">
                <div className="overflow-y-auto custom-scrollbar flex-1">
                    <table className="w-full text-left border-collapse">
                        <thead className="sticky top-0 bg-gray-50 z-10 border-b-2 border-terminal-border">
                            <tr>
                                <th className="p-6 text-[10px] font-black uppercase tracking-widest text-terminal-muted">ID Transaksi</th>
                                <th className="p-6 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Waktu</th>
                                <th className="p-6 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Kasir</th>
                                <th className="p-6 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Kategori</th>
                                <th className="p-6 text-[10px] font-black uppercase tracking-widest text-terminal-muted text-right">Total</th>
                                <th className="p-6 text-[10px] font-black uppercase tracking-widest text-terminal-muted text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-terminal-border">
                            {history.map(trx => (
                                <tr 
                                    key={trx.id} 
                                    onClick={() => onSelect(trx)}
                                    className="hover:bg-orange-50 cursor-pointer transition-colors group"
                                >
                                    <td className="p-6">
                                        <div className="font-black text-terminal-text group-hover:text-orange-600">#{trx.code}</div>
                                        <div className="text-[10px] text-terminal-muted font-bold uppercase tracking-widest">Meja {trx.table?.name}</div>
                                    </td>
                                    <td className="p-6">
                                        <div className="font-bold text-sm">{new Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                                        <div className="text-[10px] text-terminal-muted font-bold tracking-widest uppercase">{new Date(trx.created_at).toLocaleDateString('id-ID')}</div>
                                    </td>
                                    <td className="p-6">
                                        <div className="flex items-center gap-2">
                                            <div className="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-black text-xs">
                                                {trx.kasir?.name?.charAt(0) || 'K'}
                                            </div>
                                            <span className="font-bold text-sm">{trx.kasir?.name || 'Kasir'}</span>
                                        </div>
                                    </td>
                                    <td className="p-6">
                                        <Badge color={
                                            trx.guest_category === 'MAJAR_OWNER' ? 'bg-purple-100 text-purple-600' :
                                            trx.guest_category === 'MAJAR_PRIORITY' ? 'bg-blue-100 text-blue-600' :
                                            trx.guest_category === 'RESERVED' ? 'bg-orange-100 text-orange-600' :
                                            'bg-gray-100 text-gray-600'
                                        }>
                                            {trx.guest_category}
                                        </Badge>
                                    </td>
                                    <td className="p-6 text-right font-black text-lg text-terminal-text">
                                        {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(trx.total)}
                                    </td>
                                    <td className="p-6 text-center">
                                        {trx.stage === 'VOID' ? (
                                            <Badge color="bg-red-100 text-red-600">VOID</Badge>
                                        ) : (
                                            <Badge color="bg-green-100 text-green-600">PAID</Badge>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );

    const AuditDetailModal = ({ trx, onClose, onVoid }) => {
        if (!trx) return null;
        
        return (
            <div className="fixed inset-0 bg-black/80 backdrop-blur-xl z-[150] flex items-center justify-center p-4">
                <div className="bg-white rounded-[3rem] w-full max-w-5xl h-[85vh] overflow-hidden shadow-2xl flex animate-in zoom-in duration-300 border border-terminal-border">
                    <div className="flex-1 flex flex-col p-10 bg-terminal-bg/30 overflow-hidden">
                        <div className="flex justify-between items-start mb-8">
                            <div>
                                <h3 className="text-3xl font-black uppercase tracking-tighter">Detail Audit Transaksi</h3>
                                <p className="text-terminal-muted font-bold text-sm tracking-widest uppercase">ID: #{trx.code} | MEJA {trx.table?.name}</p>
                            </div>
                            <button onClick={onClose} className="w-12 h-12 rounded-full bg-white border border-terminal-border flex items-center justify-center text-terminal-muted hover:text-terminal-danger transition-all">
                                <i className="bi bi-x-lg text-xl"></i>
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto custom-scrollbar pr-4 space-y-6">
                            {/* Audit Trail Timeline */}
                            <div className="bg-white p-8 rounded-3xl border border-terminal-border shadow-sm">
                                <h4 className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-6">Audit Trail (Timeline)</h4>
                                <div className="space-y-6 relative before:absolute before:left-[15px] before:top-2 before:bottom-2 before:w-0.5 before:bg-terminal-border">
                                    {[
                                        { label: 'Pesanan Dibuat', time: trx.created_at, user: trx.waiter?.name, icon: 'bi-plus-circle-fill', color: 'text-orange-500' },
                                        { label: 'Approval & Bayar', time: trx.ordered_at || trx.paid_at, user: trx.kasir?.name, icon: 'bi-check-circle-fill', color: 'text-green-500' },
                                        { label: 'Mulai Masak', time: trx.cooking_at, user: trx.kitchen?.name, icon: 'bi-fire', color: 'text-orange-600' },
                                        { label: 'Selesai Masak', time: trx.kitchen_done_at, user: trx.kitchen?.name, icon: 'bi-cup-hot-fill', color: 'text-blue-500' },
                                        { label: 'Disajikan', time: trx.served_at, user: trx.waiter?.name, icon: 'bi-person-check-fill', color: 'text-green-600' }
                                    ].filter(step => step.time).map((step, idx) => (
                                        <div key={idx} className="flex gap-6 items-start relative z-10">
                                            <div className={`w-8 h-8 rounded-full bg-white border-2 border-terminal-border flex items-center justify-center ${step.color} shadow-sm`}>
                                                <i className={`bi ${step.icon} text-xs`}></i>
                                            </div>
                                            <div>
                                                <div className="font-black text-sm uppercase tracking-wider">{step.label}</div>
                                                <div className="text-xs text-terminal-muted font-bold">
                                                    {new Date(step.time).toLocaleTimeString('id-ID')} oleh <span className="text-terminal-text">{step.user || 'Sistem'}</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Item Details */}
                            <div className="bg-white p-8 rounded-3xl border border-terminal-border shadow-sm">
                                <h4 className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-6">Rincian Item</h4>
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b border-terminal-border">
                                            <th className="text-left py-4 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Menu</th>
                                            <th className="text-center py-4 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Qty</th>
                                            <th className="text-right py-4 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Harga</th>
                                            <th className="text-right py-4 text-[10px] font-black uppercase tracking-widest text-terminal-muted">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {trx.items.map((item, idx) => (
                                            <tr key={idx}>
                                                <td className="py-4">
                                                    <div className="font-black text-terminal-text">{item.menu_name}</div>
                                                    {item.note && <div className="text-[10px] text-orange-500 font-bold italic">Note: {item.note}</div>}
                                                </td>
                                                <td className="py-4 text-center font-bold">{item.qty}</td>
                                                <td className="py-4 text-right font-bold">{new Intl.NumberFormat('id-ID').format(item.price)}</td>
                                                <td className="py-4 text-right font-black">{new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div className="w-[380px] bg-white border-l border-terminal-border p-10 flex flex-col justify-between">
                        <div className="space-y-8">
                            <div>
                                <label className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-4 block">Informasi Pembayaran</label>
                                <div className="bg-orange-50 rounded-3xl p-6 border border-orange-100 space-y-4">
                                    <div className="flex justify-between items-center">
                                        <span className="text-[10px] font-black text-orange-800 uppercase tracking-widest">Metode</span>
                                        <span className="font-black text-orange-600 uppercase">{trx.payment_method}</span>
                                    </div>
                                    {trx.voucher_code && (
                                        <div className="flex justify-between items-center">
                                            <span className="text-[10px] font-black text-orange-800 uppercase tracking-widest">Voucher</span>
                                            <span className="font-black text-orange-600 uppercase">{trx.voucher_code}</span>
                                        </div>
                                    )}
                                    <div className="pt-4 border-t border-orange-200 flex justify-between items-center">
                                        <span className="text-lg font-black text-orange-900 uppercase">Total</span>
                                        <span className="text-2xl font-black text-orange-600">
                                            {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(trx.total)}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {trx.stage === 'VOID' && (
                                <div className="bg-red-50 rounded-3xl p-6 border border-red-100">
                                    <label className="text-[10px] font-black text-red-800 uppercase tracking-widest mb-2 block">Alasan Void</label>
                                    <p className="text-sm font-bold text-red-600 italic">"{trx.void_reason || 'Tidak ada alasan'}"</p>
                                </div>
                            )}
                        </div>

                        <div className="space-y-4">
                            <Button variant="primary" className="w-full py-5 bg-orange-500 border-none shadow-lg shadow-orange-500/20" icon="bi-printer-fill" onClick={() => printReceipt('STRUK', trx)}>RE-PRINT STRUK</Button>
                            {trx.stage !== 'VOID' && (
                                <Button variant="secondary" className="w-full py-5 text-terminal-danger hover:bg-red-50" icon="bi-trash-fill" onClick={() => onVoid(trx)}>VOID TRANSAKSI</Button>
                            )}
                            <Button variant="dark" className="w-full py-5" onClick={onClose}>TUTUP</Button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const ReportsView = ({ reportData }) => (
        <div className="flex-1 flex flex-col p-8 bg-terminal-bg/30 overflow-y-auto custom-scrollbar">
            <h2 className="text-3xl font-black uppercase tracking-tighter mb-8 text-terminal-text">Ikhtisar Penjualan</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div className="bg-white p-8 rounded-[2rem] border border-terminal-border shadow-sm group hover:border-orange-500 transition-all">
                    <div className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-2 group-hover:text-orange-500">Total Penjualan</div>
                    <div className="text-3xl font-black tracking-tighter text-terminal-text">{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(reportData.total_sales || 0)}</div>
                </div>
                <div className="bg-white p-8 rounded-[2rem] border border-terminal-border shadow-sm group hover:border-orange-500 transition-all">
                    <div className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-2 group-hover:text-orange-500">Transaksi Sukses</div>
                    <div className="text-3xl font-black tracking-tighter text-terminal-text">{reportData.order_count || 0}</div>
                </div>
                <div className="bg-white p-8 rounded-[2rem] border border-terminal-border shadow-sm group hover:border-red-500 transition-all">
                    <div className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-2 group-hover:text-red-500 text-terminal-danger">Dibatalkan (Void)</div>
                    <div className="text-3xl font-black tracking-tighter text-terminal-danger">{reportData.void_count || 0}</div>
                </div>
                <div className="bg-white p-8 rounded-[2rem] border border-terminal-border shadow-sm group hover:border-orange-500 transition-all">
                    <div className="text-[10px] font-black text-terminal-muted uppercase tracking-[0.3em] mb-2 group-hover:text-orange-500">Rata-rata Bill</div>
                    <div className="text-3xl font-black tracking-tighter text-terminal-text">
                        {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format((reportData.total_sales / (reportData.order_count || 1)) || 0)}
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div className="bg-white p-10 rounded-[3rem] border border-terminal-border shadow-sm">
                    <h3 className="text-xl font-black uppercase tracking-widest mb-8 border-b pb-4 border-terminal-border">Penjualan per Kategori</h3>
                    <div className="space-y-6">
                        {(reportData.category_sales || []).map(cat => (
                            <div key={cat.category} className="space-y-2">
                                <div className="flex justify-between items-end">
                                    <span className="font-black uppercase text-[10px] tracking-widest text-terminal-muted">{cat.category}</span>
                                    <span className="font-black text-terminal-text">{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(cat.total)}</span>
                                </div>
                                <div className="h-3 bg-terminal-bg rounded-full overflow-hidden border border-terminal-border">
                                    <div className="h-full bg-orange-500 rounded-full" style=@{{ width: `${(cat.total / (reportData.total_sales || 1)) * 100}%` }}></div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
                <div className="bg-white p-10 rounded-[3rem] border border-terminal-border shadow-sm flex flex-col items-center justify-center text-center space-y-4 opacity-50">
                    <i className="bi bi-pie-chart-fill text-8xl text-orange-200"></i>
                    <p className="font-bold text-terminal-muted uppercase tracking-widest text-xs">Visualisasi Grafik Segera Hadir</p>
                </div>
            </div>
        </div>
    );

    const SettingsView = () => (
        <div className="flex-1 flex flex-col p-8 bg-terminal-bg/30 overflow-y-auto custom-scrollbar">
            <h2 className="text-3xl font-black uppercase tracking-tighter mb-8">Pengaturan Perangkat</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div className="bg-white p-8 rounded-3xl border border-terminal-border shadow-sm space-y-6">
                    <h3 className="text-xl font-black uppercase tracking-widest border-b pb-4 border-terminal-border">Thermal Printer</h3>
                    <div className="flex justify-between items-center">
                        <div>
                            <div className="font-black">Printer Bluetooth POS-58</div>
                            <div className="text-xs text-terminal-accent font-bold">Terhubung</div>
                        </div>
                        <Button size="sm" variant="secondary">Putuskan</Button>
                    </div>
                    <Button variant="primary" icon="bi-lightning-charge" className="w-full">Test Print</Button>
                </div>
                <div className="bg-white p-8 rounded-3xl border border-terminal-border shadow-sm space-y-6">
                    <h3 className="text-xl font-black uppercase tracking-widest border-b pb-4 border-terminal-border">Cash Drawer</h3>
                    <div className="flex justify-between items-center">
                        <div>
                            <div className="font-black">USB Cash Drawer</div>
                            <div className="text-xs text-terminal-muted font-bold">Tidak Terdeteksi</div>
                        </div>
                        <Button size="sm" variant="primary">Hubungkan</Button>
                    </div>
                    <Button variant="secondary" icon="bi-box-arrow-up" className="w-full">Buka Laci</Button>
                </div>
            </div>
        </div>
    );

    const Numpad = ({ value, onChange, onConfirm, onCancel, total }) => {
        const buttons = [1, 2, 3, 4, 5, 6, 7, 8, 9, '000', 0, 'C'];
        const quickCash = [total, 50000, 100000, 200000];

        const handleKey = (key) => {
            if (key === 'C') onChange(0);
            else if (key === '000') onChange(parseInt(value.toString() + '000') || 0);
            else onChange(parseInt(value.toString() + key.toString()) || 0);
        };

        const change = value - total;

        return (
            <div className="fixed inset-0 bg-black/80 backdrop-blur-xl z-[150] flex items-center justify-center p-4">
                <div className="bg-white rounded-[3rem] w-full max-w-4xl overflow-hidden shadow-2xl flex animate-in zoom-in duration-300">
                    <div className="flex-1 p-10 bg-terminal-bg/30">
                        <div className="mb-8">
                            <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mb-2 block">Uang Diterima (CASH)</label>
                            <div className="text-7xl font-black text-orange-600 tracking-tighter">
                                {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)}
                            </div>
                        </div>
                        
                        <div className="grid grid-cols-3 gap-4 mb-8">
                            {buttons.map(btn => (
                                <button 
                                    key={btn}
                                    onClick={() => handleKey(btn)}
                                    className={`h-24 rounded-[2rem] text-3xl font-black transition-all active:scale-90 ${btn === 'C' ? 'bg-red-50 text-red-600 border-2 border-red-100' : 'bg-white border-2 border-terminal-border text-terminal-text hover:border-orange-500 hover:text-orange-500 shadow-sm'}`}
                                >
                                    {btn}
                                </button>
                            ))}
                        </div>

                        <div className="flex gap-4">
                            <Button variant="secondary" className="flex-1 py-8 text-lg" onClick={onCancel}>BATAL</Button>
                            <Button variant="primary" className="flex-1 py-8 text-lg bg-orange-500 border-none shadow-xl shadow-orange-500/30" onClick={onConfirm} disabled={value < total}>KONFIRMASI BAYAR</Button>
                        </div>
                    </div>

                    <div className="w-[350px] bg-white border-l border-terminal-border p-10 flex flex-col justify-between">
                        <div>
                            <div className="mb-10">
                                <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mb-2 block">Total Tagihan</label>
                                <div className="text-3xl font-black text-terminal-muted">
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total)}
                                </div>
                            </div>

                            <div className="mb-10">
                                <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mb-2 block">Kembalian</label>
                                <div className={`text-7xl font-black leading-none tracking-tighter ${change >= 0 ? 'text-green-500' : 'text-terminal-danger'}`}>
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.max(0, change))}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-3">
                            <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest block">Uang Pas / Cepat</label>
                            {quickCash.map(amount => (
                                <button 
                                    key={amount}
                                    onClick={() => onChange(amount)}
                                    className="w-full py-4 border-2 border-terminal-border rounded-2xl font-black text-terminal-muted hover:border-terminal-accent hover:text-terminal-accent transition-all active:scale-95"
                                >
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount)}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const SplitBillModal = ({ order, onConfirm, onCancel }) => {
        const [selectedItems, setSelectedItems] = useState([]);
        
        const toggleItem = (idx, maxQty) => {
            const existing = selectedItems.find(i => i.idx === idx);
            if (existing) {
                if (existing.qty < maxQty) {
                    setSelectedItems(selectedItems.map(i => i.idx === idx ? {...i, qty: i.qty + 1} : i));
                } else {
                    setSelectedItems(selectedItems.filter(i => i.idx !== idx));
                }
            } else {
                setSelectedItems([...selectedItems, { idx, qty: 1, item: order.items[idx] }]);
            }
        };

        const splitTotal = selectedItems.reduce((sum, i) => sum + (i.item.price * i.qty), 0);

        return (
            <div className="fixed inset-0 bg-black/80 backdrop-blur-xl z-[150] flex items-center justify-center p-4">
                <div className="bg-white rounded-[3rem] w-full max-w-4xl h-[80vh] overflow-hidden shadow-2xl flex animate-in zoom-in duration-300">
                    <div className="flex-1 flex flex-col p-10 bg-terminal-bg/30">
                        <h3 className="text-3xl font-black uppercase tracking-tighter mb-2">Split Bill</h3>
                        <p className="text-terminal-muted font-bold mb-8 text-sm">Pilih item yang akan dipindah ke Bill Baru.</p>
                        
                        <div className="flex-1 overflow-y-auto space-y-3 custom-scrollbar pr-4">
                            {order.items.map((item, idx) => {
                                const selected = selectedItems.find(i => i.idx === idx);
                                return (
                                    <div 
                                        key={idx} 
                                        onClick={() => toggleItem(idx, item.qty)}
                                        className={`p-4 rounded-2xl border-2 transition-all cursor-pointer flex justify-between items-center ${selected ? 'border-terminal-accent bg-terminal-accent/5' : 'border-white bg-white hover:border-terminal-border'}`}
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className={`w-10 h-10 rounded-full flex items-center justify-center font-black ${selected ? 'bg-terminal-accent text-white' : 'bg-terminal-bg text-terminal-muted'}`}>
                                                {selected ? selected.qty : item.qty}
                                            </div>
                                            <div>
                                                <div className="font-black text-terminal-text">{item.menu_name}</div>
                                                <div className="text-xs text-terminal-muted font-bold">{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price)}</div>
                                            </div>
                                        </div>
                                        {selected && <i className="bi bi-check-circle-fill text-terminal-accent text-2xl"></i>}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <div className="w-[350px] bg-white border-l border-terminal-border p-10 flex flex-col justify-between">
                        <div>
                            <div className="mb-10">
                                <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mb-2 block">Total Bill Baru</label>
                                <div className="text-5xl font-black text-terminal-accent tracking-tighter">
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(splitTotal)}
                                </div>
                                <div className="text-xs font-bold text-terminal-muted mt-2">({selectedItems.length} item terpilih)</div>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Button variant="secondary" className="w-full py-5" onClick={onCancel}>BATAL</Button>
                            <Button variant="primary" className="w-full py-5" disabled={selectedItems.length === 0} onClick={() => onConfirm(selectedItems)}>PINDAH KE BILL BARU</Button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    // --- Main Application ---
    const KasirTerminal = () => {
        const [activeTab, setActiveTab] = useState('PENDING'); 
        const [activeOrder, setActiveOrder] = useState(null);
        const [paymentMethod, setPaymentMethod] = useState('cash');
        const [amountPaid, setAmountPaid] = useState(0);
        const [isProcessing, setIsProcessing] = useState(false);
        const [isEditing, setIsEditing] = useState(false);
        const [couponCode, setCouponCode] = useState('');
        const [appliedCoupon, setAppliedCoupon] = useState(null);
        const [discountPercent, setDiscountPercent] = useState(0);

        // Voucher States
        const [voucherCode, setVoucherCode] = useState('');
        const [appliedVoucher, setAppliedVoucher] = useState(null);
        const [voucherDiscount, setVoucherDiscount] = useState(0);
        
        const [orders, setOrders] = useState([]);
        const [history, setHistory] = useState([]);
        const [reportData, setReportData] = useState({});
        const [menuItems] = useState(@json($menuItems));
        const [categories] = useState(['All', ...@json($categories)]);
        const [tables] = useState(@json($tables));

        // UI States
        const [showVoidModal, setShowVoidModal] = useState(false);
        const [showNumpad, setShowNumpad] = useState(false);
        const [showSplitModal, setShowSplitModal] = useState(false);
        const [voidTarget, setVoidTarget] = useState(null);
        const [voidReason, setVoidReason] = useState('');
        const [voidPin, setVoidPin] = useState('');
        const [voidType, setVoidType] = useState('ORDER'); // 'ORDER' or 'ITEM'
        const [voidItemIdx, setVoidItemIdx] = useState(null);

        const fetchOrders = useCallback(async () => {
            try {
                const response = await fetch('/terminal/orders?role=kasir', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to fetch orders');
                const data = await response.json();
                setOrders(data.filter(o => o.stage === 'WAITING_CASHIER'));
            } catch (e) { console.error('Failed to fetch orders', e); }
        }, []);

        const fetchHistory = useCallback(async () => {
            try {
                const response = await fetch('/terminal/orders/history', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to fetch history');
                const data = await response.json();
                setHistory(data);
            } catch (e) { console.error('Failed to fetch history', e); }
        }, []);

        const fetchReports = useCallback(async () => {
            try {
                const response = await fetch('/terminal/reports/summary', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to fetch reports');
                const data = await response.json();
                setReportData(data);
            } catch (e) { console.error('Failed to fetch reports', e); }
        }, []);

        useEffect(() => {
            fetchOrders();
            const interval = setInterval(fetchOrders, 10000);
            return () => clearInterval(interval);
        }, [fetchOrders]);

        useEffect(() => {
            if (activeTab === 'HISTORY') fetchHistory();
            if (activeTab === 'REPORTS') fetchReports();
        }, [activeTab]);

        const handleSelectOrder = (order) => {
            const orderCopy = JSON.parse(JSON.stringify(order));
            // Ensure all items have a type (DINE_IN/TAKE_AWAY)
            orderCopy.items = orderCopy.items.map(i => ({ ...i, type: i.type || orderCopy.order_type || 'DINE_IN' }));
            setActiveOrder(orderCopy);
            setAmountPaid(orderCopy.total * 1.16); // Total with tax/service
            setPaymentMethod('cash');
            setIsEditing(false);
            setCouponCode('');
            setAppliedCoupon(null);
            setDiscountPercent(0);
            setVoucherCode('');
            setAppliedVoucher(null);
            setVoucherDiscount(0);
        };

        const handleAddItem = (menu) => {
            if (!activeOrder) {
                const newOrder = {
                    id: null,
                    table_id: tables[0]?.id || 1,
                    table: tables[0] || { name: 'Direct' },
                    code: 'POS-' + Date.now().toString().slice(-6),
                    items: [],
                    total: 0,
                    guest_category: 'REGULER',
                    order_type: 'TAKE_AWAY'
                };
                setActiveOrder(newOrder);
                updateItems(newOrder, menu);
            } else {
                updateItems(activeOrder, menu);
            }
        };

        const updateItems = (order, menu) => {
            const newItems = [...order.items];
            const existing = newItems.find(i => i.menu_item_id === menu.id);
            if (existing) {
                existing.qty++;
            } else {
                newItems.push({
                    menu_item_id: menu.id,
                    menu_name: menu.name,
                    price: menu.price,
                    qty: 1,
                    note: '',
                    type: order.order_type || 'DINE_IN'
                });
            }
            recalculate(order, newItems);
        };

        const recalculate = (order, items) => {
            const subtotal = items.reduce((sum, i) => sum + (i.price * i.qty), 0);
            const totalWithTax = subtotal * 1.16; // 5% service + 11% tax
            setActiveOrder({ ...order, items, total: subtotal });
            setAmountPaid(totalWithTax * (1 - discountPercent/100));
        };

        const handleUpdateQty = (index, delta) => {
            const newItems = [...activeOrder.items];
            newItems[index].qty += delta;
            if (newItems[index].qty <= 0) {
                handleVoidItemRequest(index);
                return;
            }
            recalculate(activeOrder, newItems);
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
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ code: couponCode })
                });
                const data = await response.json();
                if (!response.ok) {
                    alert(data.error || data.message || 'Gagal mengecek kupon');
                } else {
                    setAppliedCoupon(data.code);
                    setDiscountPercent(data.discount_percent);
                    const subtotal = activeOrder.total;
                    const totalWithTax = subtotal * 1.16;
                    setAmountPaid(totalWithTax * (1 - data.discount_percent/100));
                }
            } catch (e) { alert('Error: ' + e.message); }
        };

    const handleCheckVoucher = async () => {
        if (!voucherCode) return;
        try {
            const cartCategories = activeOrder.items.map(item => {
                const menuItem = menuItems.find(m => m.id === item.menu_item_id);
                return menuItem ? menuItem.category : null;
            }).filter(Boolean);

            const response = await fetch('/terminal/vouchers/check', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify({ code: voucherCode, cart_categories: cartCategories })
            });
            const data = await response.json();
            if (!response.ok) {
                alert(data.error || data.message || 'Gagal mengecek voucher');
            } else {
                setAppliedVoucher(data.code);
                let discountAmount = 0;
                const subtotal = activeOrder.total;
                const totalWithTax = subtotal * 1.16;

                if (data.type === 'percentage') {
                    discountAmount = totalWithTax * (data.value / 100);
                } else {
                    discountAmount = data.value;
                }
                
                setVoucherDiscount(discountAmount);
                setAmountPaid(totalWithTax * (1 - discountPercent/100) - discountAmount);
            }
        } catch (e) { alert('Error: ' + e.message); }
    };

    const printReceipt = (type = 'STRUK', orderData = null) => {
        const data = orderData || activeOrder;
        if (!data) return;

        const subtotal = data.items.reduce((sum, i) => sum + (i.price * i.qty), 0);
        const discount = data.discount || 0;
        const tax = (subtotal - discount) * 0.11;
        const service = subtotal * 0.05;
        const grandTotal = subtotal - discount + tax + service;

        const printWindow = window.open('', '_blank', 'width=400,height=600');
        printWindow.document.write(`
            <html>
            <head>
                <style>
                    @page { margin: 0; }
                    body { font-family: 'Courier New', Courier, monospace; font-size: 12px; padding: 20px; width: 300px; line-height: 1.2; }
                    .center { text-align: center; }
                    .bold { font-weight: bold; }
                    .header { margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
                    .title { font-size: 18px; margin-bottom: 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                    .item-name { padding-top: 5px; }
                    .item-detail { font-size: 10px; color: #666; padding-bottom: 5px; border-bottom: 1px dotted #eee; }
                    .totals { border-top: 1px dashed #000; padding-top: 10px; }
                    .footer { margin-top: 30px; border-top: 1px dashed #000; padding-top: 10px; font-size: 10px; }
                    .grand-total { font-size: 16px; border-top: 1px double #000; padding-top: 5px; margin-top: 5px; }
                </style>
            </head>
            <body>
                <div class="header center">
                    <div class="title bold">MAJAR SIGNATURE</div>
                    <div>Jl. Raya Majar No. 88, Jakarta</div>
                    <div>Telp: (021) 555-1234</div>
                    <div style="margin-top: 10px;">
                        ID: #${data.code}<br>
                        Kasir: ${data.kasir?.name || 'Sistem'}<br>
                        Meja: ${data.table?.name || '-'}<br>
                        Waktu: ${new Date().toLocaleString('id-ID')}
                    </div>
                </div>

                <table>
                    <thead>
                        <tr class="bold">
                            <th align="left">ITEM</th>
                            <th align="center">QTY</th>
                            <th align="right">SUB</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.items.map(item => `
                            <tr>
                                <td class="item-name bold">${item.menu_name}</td>
                                <td align="center">${item.qty}</td>
                                <td align="right">${new Intl.NumberFormat('id-ID').format(item.price * item.qty)}</td>
                            </tr>
                            ${item.note ? `<tr><td colspan="3" class="item-detail italic">Note: ${item.note}</td></tr>` : ''}
                        `).join('')}
                    </tbody>
                </table>

                <div class="totals">
                    <div style="display:flex; justify-content: space-between;"><span>Subtotal</span><span>${new Intl.NumberFormat('id-ID').format(subtotal)}</span></div>
                    ${discount > 0 ? `<div style="display:flex; justify-content: space-between; color: red;"><span>Discount</span><span>-${new Intl.NumberFormat('id-ID').format(discount)}</span></div>` : ''}
                    <div style="display:flex; justify-content: space-between;"><span>Service (5%)</span><span>${new Intl.NumberFormat('id-ID').format(service)}</span></div>
                    <div style="display:flex; justify-content: space-between;"><span>Tax (11%)</span><span>${new Intl.NumberFormat('id-ID').format(tax)}</span></div>
                    <div class="grand-total bold" style="display:flex; justify-content: space-between;">
                        <span>GRAND TOTAL</span>
                        <span>${new Intl.NumberFormat('id-ID').format(grandTotal)}</span>
                    </div>
                </div>

                <div style="margin-top: 15px; font-size: 10px;">
                    <div style="display:flex; justify-content: space-between;"><span>Bayar: ${data.payment_method?.toUpperCase() || 'CASH'}</span><span>${new Intl.NumberFormat('id-ID').format(data.amount_paid || grandTotal)}</span></div>
                    ${data.amount_paid > grandTotal ? `<div style="display:flex; justify-content: space-between;"><span>Kembalian</span><span>${new Intl.NumberFormat('id-ID').format(data.amount_paid - grandTotal)}</span></div>` : ''}
                </div>

                <div class="footer center">
                    <p class="bold text-orange-500">Terima kasih telah berkunjung ke Majar Signature!</p>
                    <p>Follow us @majarsignature</p>
                </div>
                <script>
                    window.onload = function() { window.print(); window.close(); }
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    };

        const handleProcessPayment = async () => {
            if (paymentMethod === 'cash' && !showNumpad) {
                setShowNumpad(true);
                return;
            }
            
            setIsProcessing(true);
            try {
                const response = await fetch(`/terminal/orders/${activeOrder.id || 'new'}/approve-and-pay`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({
                        table_id: activeOrder.table_id,
                        customer_name: activeOrder.customer_name,
                        payment_method: paymentMethod,
                        amount_paid: amountPaid,
                        coupon_code: appliedCoupon,
                        voucher_code: appliedVoucher,
                        discount_percent: discountPercent,
                        guest_category: activeOrder.guest_category,
                        order_type: activeOrder.order_type,
                        items: activeOrder.items.map(i => ({ menu_item_id: i.menu_item_id, qty: i.qty, note: i.note, type: i.type }))
                    })
                });
                
                const data = await response.json();
                if (response.ok) {
                    printReceipt('STRUK', data.order);
                    printReceipt('KITCHEN', data.order);
                    setActiveOrder(null);
                    setShowNumpad(false);
                    fetchOrders();
                } else {
                    throw new Error(data.error || data.message || 'Gagal memproses transaksi');
                }
            } catch (e) { alert('Error: ' + e.message); }
            finally { setIsProcessing(false); }
        };

        const handleVoidRequest = (trx) => {
            setVoidTarget(trx);
            setVoidType('ORDER');
            setShowVoidModal(true);
            setVoidReason('');
            setVoidPin('');
        };

        const handleVoidItemRequest = (idx) => {
            // Only require PIN if the order is already saved in DB (has ID)
            if (!activeOrder.id) {
                const newItems = [...activeOrder.items];
                newItems.splice(idx, 1);
                recalculate(activeOrder, newItems);
                return;
            }
            setVoidItemIdx(idx);
            setVoidType('ITEM');
            setShowVoidModal(true);
            setVoidReason('');
            setVoidPin('');
        };

        const confirmVoid = async () => {
            if (!voidReason || !voidPin) return;
            if (voidPin !== '1234') { alert('PIN Manager Salah!'); return; }

            if (voidType === 'ITEM') {
                const newItems = [...activeOrder.items];
                newItems.splice(voidItemIdx, 1);
                recalculate(activeOrder, newItems);
                setShowVoidModal(false);
                return;
            }

            setIsProcessing(true);
            try {
                const response = await fetch(`/terminal/orders/${voidTarget.id}/void`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ reason: voidReason, pin: voidPin })
                });
                const result = await response.json();
                if (response.ok) {
                    alert('Transaksi berhasil di-void!');
                    setShowVoidModal(false);
                    fetchHistory();
                } else {
                    throw new Error(result.error || result.message || 'Gagal melakukan void');
                }
            } catch (e) { alert('Error: ' + e.message); }
            finally { setIsProcessing(false); }
        };

        const handleSplitConfirm = async (selectedItems) => {
            if (!activeOrder.id) { alert('Hanya pesanan tersimpan yang bisa di-split'); return; }
            setIsProcessing(true);
            try {
                const response = await fetch(`/terminal/orders/${activeOrder.id}/split`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({
                        items: selectedItems.map(i => ({ order_item_id: i.item.id, qty: i.qty }))
                    })
                });
                const data = await response.json();
                if (response.ok) {
                    alert('Bill berhasil dipisah!');
                    setShowSplitModal(false);
                    setActiveOrder(null);
                    fetchOrders();
                } else {
                    throw new Error(data.error || data.message || 'Gagal melakukan split bill');
                }
            } catch (e) { alert('Error: ' + e.message); }
            finally { setIsProcessing(false); }
        };

        const handleMergeTable = async () => {
            if (!activeOrder.id) { alert('Hanya pesanan tersimpan yang bisa digabung'); return; }
            const tableId = prompt("Masukkan ID Meja yang akan digabung ke sini:");
            if (!tableId) return;
            
            try {
                const response = await fetch(`/terminal/orders/${activeOrder.id}/merge`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ source_table_id: tableId })
                });
                const result = await response.json();
                if (response.ok) {
                    alert('Meja berhasil digabung!');
                    handleSelectOrder(result.order);
                    fetchOrders();
                } else {
                    alert(result.error || result.message || 'Gagal menggabungkan meja');
                }
            } catch (e) { alert('Error: ' + e.message); }
        };

        const GUEST_COLORS = {
            'REGULER': 'bg-terminal-accent',
            'RESERVED': 'bg-terminal-warning',
            'MAJAR_PRIORITY': 'bg-blue-500',
            'MAJAR_OWNER': 'bg-purple-500'
        };

        return (
            <div className="flex w-full h-full bg-white overflow-hidden font-sans">
                {/* Sidebar Navigation */}
                <div className="w-32 bg-white border-r border-terminal-border flex flex-col flex-shrink-0 z-30 shadow-2xl">
                    <div className="p-4 border-b border-terminal-border bg-terminal-accent/5">
                        <img src="/logo.png" className="w-full h-auto grayscale opacity-50" alt="Majar" />
                    </div>
                    <SidebarItem id="ORDERING" label="Ordering" icon="bi-grid-fill" active={activeTab === 'ORDERING'} onClick={setActiveTab} />
                    <SidebarItem id="PENDING" label="Pending" icon="bi-hourglass-split" active={activeTab === 'PENDING'} onClick={setActiveTab} />
                    <SidebarItem id="HISTORY" label="History" icon="bi-receipt-cutoff" active={activeTab === 'HISTORY'} onClick={setActiveTab} />
                    <SidebarItem id="REPORTS" label="Reports" icon="bi-graph-up-arrow" active={activeTab === 'REPORTS'} onClick={setActiveTab} />
                    <div className="mt-auto">
                        <SidebarItem id="SETTINGS" label="Settings" icon="bi-gear-fill" active={activeTab === 'SETTINGS'} onClick={setActiveTab} />
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex flex-col relative overflow-hidden">
                    {activeTab === 'ORDERING' && <OrderingView menuItems={menuItems} categories={categories} tables={tables} onAddItem={handleAddItem} />}
                    {activeTab === 'PENDING' && <PendingApprovalView orders={orders} onSelect={handleSelectOrder} />}
                {activeTab === 'HISTORY' && <TransactionHistoryView history={history} onVoid={handleVoidRequest} onSelect={setActiveOrder} />}
                {activeTab === 'REPORTS' && <ReportsView reportData={reportData} />}
                {activeTab === 'SETTINGS' && <SettingsView />}

                {/* Audit Detail Modal */}
                {activeTab === 'HISTORY' && activeOrder && !isEditing && (
                    <AuditDetailModal 
                        trx={activeOrder} 
                        onClose={() => setActiveOrder(null)} 
                        onVoid={handleVoidRequest} 
                    />
                )}
                </div>

                {/* Right: Active Order / Cart (Persistent for Ordering/Pending) */}
                {(activeTab === 'ORDERING' || (activeTab === 'PENDING' && activeOrder)) && (
                    <div className="w-[400px] flex flex-col bg-white border-l border-terminal-border shadow-2xl z-20">
                        {!activeOrder ? (
                            <div className="flex-1 flex flex-col items-center justify-center opacity-10 text-terminal-muted p-8 text-center">
                                <i className="bi bi-cart-x text-[6rem] mb-4"></i>
                                <h3 className="text-xl font-black uppercase">Belum ada pesanan aktif</h3>
                                <p className="text-xs font-bold mt-2">Pilih menu atau antrean untuk memulai</p>
                            </div>
                        ) : (
                            <div className="flex-1 flex flex-col overflow-hidden">
                                <div className="p-6 bg-terminal-bg/50 border-b border-terminal-border">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 className="text-2xl font-black">Meja {activeOrder.table?.name}</h3>
                                            <div className="text-[10px] font-mono text-terminal-muted">#{activeOrder.code}</div>
                                        </div>
                                        <button onClick={() => setActiveOrder(null)} className="text-terminal-muted hover:text-terminal-danger"><i className="bi bi-x-circle text-2xl"></i></button>
                                    </div>
                                    <div className="flex gap-2">
                                        <div className="relative flex-1">
                                            <i className="bi bi-person-fill absolute left-3 top-1/2 -translate-y-1/2 text-orange-400"></i>
                                            <input 
                                                type="text" 
                                                className="w-full bg-white border border-orange-100 rounded-xl pl-10 pr-4 py-2 text-xs font-black uppercase focus:outline-none shadow-sm"
                                                placeholder="NAMA TAMU"
                                                value={activeOrder.customer_name || ''}
                                                onChange={e => setActiveOrder({...activeOrder, customer_name: e.target.value})}
                                            />
                                        </div>
                                        <div className="flex gap-1">
                                            <button 
                                                onClick={handleMergeTable}
                                                className="w-10 h-10 rounded-xl border border-terminal-border flex items-center justify-center text-terminal-muted hover:text-orange-500 hover:border-orange-500 bg-white shadow-sm transition-all"
                                                title="Gabung Meja"
                                            >
                                                <i className="bi bi-diagram-2"></i>
                                            </button>
                                            <button 
                                                onClick={() => setShowSplitModal(true)}
                                                className="w-10 h-10 rounded-xl border border-terminal-border flex items-center justify-center text-terminal-muted hover:text-red-500 hover:border-red-500 bg-white shadow-sm transition-all"
                                                title="Split Bill"
                                            >
                                                <i className="bi bi-scissors"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div className="mt-2">
                                        <select 
                                            className="w-full bg-white border border-terminal-border rounded-xl px-3 py-2 text-xs font-black uppercase focus:outline-none shadow-sm text-orange-600 border-orange-100"
                                            value={activeOrder.guest_category}
                                            onChange={e => setActiveOrder({...activeOrder, guest_category: e.target.value})}
                                        >
                                            <option value="REGULER">Reguler</option>
                                            <option value="RESERVED">Reserved</option>
                                            <option value="MAJAR_PRIORITY">Priority</option>
                                            <option value="MAJAR_OWNER">Owner</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                                    {activeOrder.items.map((item, idx) => (
                                        <div key={idx} className="bg-white border border-terminal-border rounded-2xl p-4 shadow-sm space-y-3 relative overflow-hidden group">
                                            {/* Item Type Badge */}
                                            <div className="absolute top-0 right-0">
                                                <button 
                                                    onClick={() => {
                                                        const newItems = [...activeOrder.items];
                                                        newItems[idx].type = item.type === 'TAKE_AWAY' ? 'DINE_IN' : 'TAKE_AWAY';
                                                        setActiveOrder({...activeOrder, items: newItems});
                                                    }}
                                                    className={`text-[8px] font-black px-2 py-1 rounded-bl-xl transition-colors ${item.type === 'TAKE_AWAY' ? 'bg-terminal-danger text-white' : 'bg-terminal-accent text-white'}`}
                                                >
                                                    {item.type === 'TAKE_AWAY' ? 'TAKE AWAY' : 'DINE IN'}
                                                </button>
                                            </div>

                                            <div className="flex justify-between items-start">
                                                <div className="font-black text-sm leading-tight flex-1 pr-8 text-terminal-text">{item.menu_name}</div>
                                                <div className="font-black text-terminal-accent text-lg">
                                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price * item.qty)}
                                                </div>
                                            </div>
                                            
                                            <div className="flex items-center justify-between gap-4">
                                                <div className="flex items-center gap-2 bg-terminal-bg p-1 rounded-lg border border-terminal-border">
                                                    <button onClick={() => handleUpdateQty(idx, -1)} className="w-8 h-8 rounded flex items-center justify-center hover:bg-black/5 text-terminal-text">-</button>
                                                    <span className="font-black min-w-[20px] text-center text-terminal-text">{item.qty}</span>
                                                    <button onClick={() => handleUpdateQty(idx, 1)} className="w-8 h-8 rounded flex items-center justify-center hover:bg-black/5 text-terminal-text">+</button>
                                                </div>
                                                
                                                <div className="flex-1 flex items-center gap-2">
                                                    <div className="relative flex-1">
                                                        <i className="bi bi-pencil-fill absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-terminal-muted"></i>
                                                        <input 
                                                            type="text" 
                                                            className="w-full bg-terminal-bg border border-terminal-border rounded-lg pl-6 pr-2 py-1.5 text-[10px] focus:outline-none focus:border-terminal-accent text-terminal-text" 
                                                            placeholder="Tambah catatan..." 
                                                            value={item.note || ''}
                                                            onChange={e => handleUpdateItemNote(idx, e.target.value)}
                                                        />
                                                    </div>
                                                    <button 
                                                        onClick={() => handleVoidItemRequest(idx)}
                                                        className="w-8 h-8 rounded-lg flex items-center justify-center text-terminal-danger hover:bg-terminal-danger/10 transition-colors"
                                                    >
                                                        <i className="bi bi-trash-fill"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="p-6 border-t-2 border-terminal-border bg-white shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
                                    <div className="space-y-3 mb-6">
                                        <div className="flex gap-2 mb-4">
                                            <div className="relative flex-1">
                                                <i className="bi bi-tag-fill absolute left-3 top-1/2 -translate-y-1/2 text-terminal-muted text-sm"></i>
                                                <input 
                                                    type="text" 
                                                    className="w-full bg-terminal-bg border border-terminal-border rounded-xl pl-10 pr-4 py-2 text-xs font-bold uppercase focus:outline-none focus:border-orange-500"
                                                    placeholder="KODE KUPON"
                                                    value={couponCode}
                                                    onChange={e => setCouponCode(e.target.value.toUpperCase())}
                                                />
                                            </div>
                                            <Button size="sm" variant="dark" onClick={handleCheckCoupon}>CEK</Button>
                                        </div>

                                        <div className="flex gap-2 mb-6">
                                            <div className="relative flex-1">
                                                <i className="bi bi-ticket-perforated absolute left-3 top-1/2 -translate-y-1/2 text-terminal-muted text-sm"></i>
                                                <input 
                                                    type="text" 
                                                    className="w-full bg-terminal-bg border border-terminal-border rounded-xl pl-10 pr-4 py-2 text-xs font-bold uppercase focus:outline-none focus:border-orange-500"
                                                    placeholder="INPUT VOUCHER CODE"
                                                    value={voucherCode}
                                                    onChange={e => setVoucherCode(e.target.value.toUpperCase())}
                                                />
                                            </div>
                                            <Button size="sm" variant="primary" className="bg-orange-500 border-none" onClick={handleCheckVoucher}>APPLY</Button>
                                        </div>

                                        <div className="flex justify-between text-terminal-muted font-bold text-xs uppercase tracking-widest"><span>Subtotal</span><span>{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(activeOrder.total)}</span></div>
                                        <div className="flex justify-between text-terminal-muted font-bold text-xs uppercase tracking-widest"><span>Service Charge (5%)</span><span>{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(activeOrder.total * 0.05)}</span></div>
                                        <div className="flex justify-between text-terminal-muted font-bold text-xs uppercase tracking-widest"><span>Tax (11%)</span><span>{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format((activeOrder.total * 1.05) * 0.11)}</span></div>
                                        {discountPercent > 0 && <div className="flex justify-between text-red-500 font-black text-xs uppercase tracking-widest"><span>Diskon ({discountPercent}%)</span><span>-{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format((activeOrder.total * 1.16) * (discountPercent/100))}</span></div>}
                                        {voucherDiscount > 0 && (
                                            <div className="flex justify-between text-red-500 font-black text-xs uppercase tracking-widest">
                                                <span>Voucher ({appliedVoucher})</span>
                                                <span>-{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(voucherDiscount)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between items-center pt-2 border-t border-terminal-border">
                                            <span className="text-xl font-black text-terminal-text uppercase tracking-tighter">Total Akhir</span>
                                            <span className="text-4xl font-black text-orange-600 tracking-tighter">
                                                {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format((activeOrder.total * 1.16) * (1 - discountPercent/100) - voucherDiscount)}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2 mb-4">
                                        {['cash', 'qris', 'card'].map(m => (
                                            <button 
                                                key={m} 
                                                onClick={() => setPaymentMethod(m)}
                                                className={`py-3 rounded-xl border-2 flex flex-col items-center justify-center gap-1 transition-all ${paymentMethod === m ? 'border-orange-500 bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'border-terminal-border bg-white text-terminal-muted hover:border-orange-200'}`}
                                            >
                                                <i className={`bi bi-${m === 'cash' ? 'cash-stack' : m === 'qris' ? 'qr-code-scan' : 'credit-card'} text-lg`}></i>
                                                <span className="text-[8px] font-black uppercase tracking-widest">{m}</span>
                                            </button>
                                        ))}
                                    </div>
                                    <Button 
                                        variant="primary" 
                                        className="w-full py-5 text-xl uppercase tracking-widest shadow-2xl bg-orange-500 border-none hover:bg-orange-600 transition-all active:scale-95" 
                                        disabled={isProcessing || activeOrder.items.length === 0}
                                        onClick={handleProcessPayment}
                                    >
                                        {isProcessing ? 'PROSES...' : activeTab === 'PENDING' ? 'APPROVE & KIRIM KE DAPUR' : 'BAYAR SEKARANG'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* --- Modals --- */}
                {showNumpad && <Numpad value={amountPaid} onChange={setAmountPaid} onConfirm={handleProcessPayment} onCancel={() => setShowNumpad(false)} total={(activeOrder.total * 1.16) * (1 - discountPercent/100)} />}
                {showSplitModal && <SplitBillModal order={activeOrder} onConfirm={handleSplitConfirm} onCancel={() => setShowSplitModal(false)} />}

                {/* --- Void Modal --- */}
                {showVoidModal && (
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] flex items-center justify-center p-4">
                        <div className="bg-white border border-terminal-border rounded-[3rem] w-full max-w-lg overflow-hidden shadow-2xl animate-in zoom-in duration-300">
                            <div className="p-10 text-center space-y-6">
                                <div className="w-20 h-20 bg-terminal-danger/10 rounded-full flex items-center justify-center mx-auto text-terminal-danger">
                                    <i className="bi bi-exclamation-triangle-fill text-4xl"></i>
                                </div>
                                <h3 className="text-3xl font-black uppercase tracking-tighter">Otorisasi Void</h3>
                                <p className="text-terminal-muted font-bold">Membatalkan pesanan <span className="text-terminal-text">#{voidTarget?.code}</span> memerlukan otorisasi manager.</p>
                                
                                <div className="space-y-4 text-left">
                                    <div>
                                        <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mb-2 block">Alasan Void</label>
                                        <textarea 
                                            className="w-full bg-terminal-bg border border-terminal-border rounded-2xl p-4 focus:outline-none focus:border-terminal-danger"
                                            rows="3"
                                            placeholder="Contoh: Kesalahan input item..."
                                            value={voidReason}
                                            onChange={e => setVoidReason(e.target.value)}
                                        ></textarea>
                                    </div>
                                    <div>
                                        <label className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mb-2 block">PIN Manager</label>
                                        <input 
                                            type="password" 
                                            className="w-full bg-terminal-bg border border-terminal-border rounded-2xl px-4 py-4 text-center text-3xl font-black tracking-[1em] focus:outline-none focus:border-terminal-danger"
                                            maxLength="4"
                                            placeholder="****"
                                            value={voidPin}
                                            onChange={e => setVoidPin(e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="p-8 bg-gray-50 border-t border-terminal-border flex gap-4">
                                <Button variant="secondary" className="flex-1" onClick={() => setShowVoidModal(false)}>BATAL</Button>
                                <Button 
                                    variant="danger" 
                                    className="flex-1" 
                                    disabled={!voidReason || voidPin.length < 4 || isProcessing}
                                    onClick={confirmVoid}
                                >
                                    KONFIRMASI VOID
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                <style>{`
                    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
                    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
                    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
                `}</style>
            </div>
        );
    };

    const root = ReactDOM.createRoot(document.getElementById('kasir-root'));
    root.render(<KasirTerminal />);
</script>
@endsection
