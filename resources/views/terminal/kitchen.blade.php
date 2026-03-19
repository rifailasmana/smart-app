@extends('layouts.terminal')

@section('title', 'Terminal Kitchen')
@section('terminal_role', 'KITCHEN')

@section('header_extra')
<div id="kitchen-header-root"></div>
@endsection

@section('content')
<div id="kitchen-root" class="w-full h-full"></div>
@endsection

@section('extra_js')
<script type="text/babel">
    const { useState, useEffect, useMemo, useCallback } = React;

    // --- Components ---
    const Badge = ({ children, color = 'bg-terminal-border' }) => (
        <span className={`${color} text-black text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-widest shadow-sm`}>
            {children}
        </span>
    );

    const OrderTicket = ({ order, onUpdateStatus }) => {
        const stageColors = {
            'READY_FOR_KITCHEN': 'border-orange-500 bg-orange-50/30',
            'COOKING': 'border-blue-500 bg-blue-50/30',
            'READY': 'border-green-500 bg-green-50/30',
            'DONE': 'border-gray-300 bg-gray-50'
        };

        return (
            <div className={`border-2 rounded-[2.5rem] flex flex-col h-full shadow-lg transition-all hover:shadow-2xl overflow-hidden ${stageColors[order.stage] || 'border-terminal-border'}`}>
                <div className="p-6 border-b border-inherit bg-white/50">
                    <div className="flex justify-between items-start mb-4">
                        <div>
                            <div className="text-3xl font-black uppercase tracking-tighter text-terminal-text">Meja {order.table?.name}</div>
                            <div className="text-[10px] font-black text-terminal-muted uppercase tracking-widest mt-1">#{order.code}</div>
                        </div>
                        <div className="text-right">
                            <div className="text-xs font-black text-terminal-text uppercase tracking-widest">{order.order_type}</div>
                            <div className="text-[10px] font-bold text-terminal-muted">{new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <span className="px-3 py-1 rounded-full bg-white border border-inherit text-[10px] font-black uppercase tracking-widest text-terminal-text">
                            {order.guest_category}
                        </span>
                        {order.is_split_bill && (
                            <span className="px-3 py-1 rounded-full bg-orange-500 text-white text-[10px] font-black uppercase tracking-widest shadow-sm">SPLIT</span>
                        )}
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar bg-white/20">
                    {order.items.map((item, idx) => (
                        <div key={idx} className="flex gap-4 items-start">
                            <div className="w-10 h-10 rounded-2xl bg-white border border-inherit flex items-center justify-center font-black text-xl shadow-sm text-terminal-text">
                                {item.qty}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="font-black text-lg leading-tight break-words text-terminal-text uppercase">{item.menu_name}</div>
                                {item.note && (
                                    <div className="mt-1 p-2 rounded-xl bg-orange-100/50 border border-orange-200 text-orange-700 text-xs font-bold italic">
                                        <i className="bi bi-pencil-fill mr-1.5"></i>{item.note}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="p-6 border-t border-inherit bg-white/50">
                    {order.stage === 'READY_FOR_KITCHEN' && (
                        <button 
                            onClick={() => onUpdateStatus(order.id, 'COOKING')}
                            className="w-full py-5 bg-orange-500 text-white font-black rounded-[1.5rem] hover:bg-orange-600 transition-all active:scale-95 shadow-xl shadow-orange-500/20 text-lg uppercase tracking-widest"
                        >
                            <i className="bi bi-fire mr-2"></i> MULAI MASAK
                        </button>
                    )}
                    {order.stage === 'COOKING' && (
                        <button 
                            onClick={() => onUpdateStatus(order.id, 'READY')}
                            className="w-full py-5 bg-blue-500 text-white font-black rounded-[1.5rem] hover:bg-blue-600 transition-all active:scale-95 shadow-xl shadow-blue-500/20 text-lg uppercase tracking-widest"
                        >
                            <i className="bi bi-check-circle mr-2"></i> SELESAI MASAK
                        </button>
                    )}
                    {order.stage === 'READY' && (
                        <button 
                            onClick={() => onUpdateStatus(order.id, 'DONE')}
                            className="w-full py-5 bg-green-500 text-white font-black rounded-[1.5rem] hover:bg-green-600 transition-all active:scale-95 shadow-xl shadow-green-500/20 text-lg uppercase tracking-widest"
                        >
                            <i className="bi bi-person-check mr-2"></i> SUDAH DISAJIKAN
                        </button>
                    )}
                    {order.stage === 'DONE' && (
                        <div className="text-center py-4 bg-gray-100 rounded-[1.5rem] border border-gray-200">
                            <span className="text-gray-400 font-black uppercase tracking-widest text-sm">PESANAN SELESAI</span>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const KitchenHeader = () => {
        const [soundEnabled, setSoundEnabled] = useState(true);
        const [filterWithNotes, setFilterWithNotes] = useState(false);

        const toggleSound = () => {
            const newVal = !soundEnabled;
            setSoundEnabled(newVal);
            window.dispatchEvent(new CustomEvent('kitchen-sound-changed', { detail: newVal }));
        };

        const toggleFilter = () => {
            const newVal = !filterWithNotes;
            setFilterWithNotes(newVal);
            window.dispatchEvent(new CustomEvent('kitchen-filter-changed', { detail: newVal }));
        };

        return (
            <div className="flex items-center gap-8">
                <div className="flex items-center gap-3 group cursor-pointer" onClick={toggleFilter}>
                    <div className={`w-6 h-6 rounded-md border-2 flex items-center justify-center transition-all ${filterWithNotes ? 'bg-terminal-accent border-terminal-accent' : 'bg-white border-terminal-border group-hover:border-terminal-accent shadow-sm'}`}>
                        {filterWithNotes && <i className="bi bi-check-lg text-white font-black"></i>}
                    </div>
                    <label className="text-xs font-black text-terminal-muted uppercase tracking-widest cursor-pointer group-hover:text-terminal-text transition-colors">Catatan Khusus</label>
                </div>
                
                <div className="flex items-center gap-3 group cursor-pointer" onClick={toggleSound}>
                    <div className={`w-12 h-6 rounded-full relative transition-colors ${soundEnabled ? 'bg-terminal-accent' : 'bg-gray-200'}`}>
                        <div className={`absolute top-1 w-4 h-4 bg-white rounded-full transition-all shadow-sm ${soundEnabled ? 'left-7' : 'left-1'}`}></div>
                    </div>
                    <label className="text-xs font-black text-terminal-muted uppercase tracking-widest cursor-pointer group-hover:text-terminal-text transition-colors">Suara Notif</label>
                </div>
            </div>
        );
    };

    const KitchenTerminal = () => {
        const [orders, setOrders] = useState([]);
        const [tables] = useState(@json($tables));
        const [soundEnabled, setSoundEnabled] = useState(true);
        const [filterWithNotes, setFilterWithNotes] = useState(false);

        const fetchOrders = useCallback(async () => {
            try {
                const response = await fetch('/terminal/orders?role=kitchen', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to fetch orders');
                const data = await response.json();
                
                setOrders(prev => {
                    // Sound notification logic
                    if (soundEnabled && data.length > prev.length) {
                        const hasNew = data.filter(o => o.stage === 'READY_FOR_KITCHEN').length > 
                                      prev.filter(o => o.stage === 'READY_FOR_KITCHEN').length;
                        if (hasNew) {
                            new Audio('/sounds/notification.mp3').play().catch(() => {});
                        }
                    }
                    return data;
                });
            } catch (e) {
                console.error('Failed to fetch kitchen orders', e);
            }
        }, [soundEnabled]);

        useEffect(() => {
            fetchOrders();
            const interval = setInterval(fetchOrders, 5000);

            const onSoundChange = (e) => setSoundEnabled(e.detail);
            const onFilterChange = (e) => setFilterWithNotes(e.detail);

            window.addEventListener('kitchen-sound-changed', onSoundChange);
            window.addEventListener('kitchen-filter-changed', onFilterChange);

            return () => {
                clearInterval(interval);
                window.removeEventListener('kitchen-sound-changed', onSoundChange);
                window.removeEventListener('kitchen-filter-changed', onFilterChange);
            };
        }, [fetchOrders]);

        const handleUpdateStatus = async (orderId, newStatus) => {
            try {
                const response = await fetch(`/terminal/orders/${orderId}/kitchen-status`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                if (response.ok) fetchOrders();
                else {
                    const data = await response.json();
                    alert('Gagal: ' + (data.error || data.message || 'Gagal mengupdate status tiket.'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        };

        const filteredOrders = useMemo(() => {
            if (!filterWithNotes) return orders;
            return orders.filter(o => o.items.some(i => i.note && i.note.trim() !== ''));
        }, [orders, filterWithNotes]);

        const columns = [
            { id: 'READY_FOR_KITCHEN', label: 'Antrian Baru', color: 'bg-terminal-accent' },
            { id: 'COOKING', label: 'Sedang Dimasak', color: 'bg-terminal-warning' },
            { id: 'READY', label: 'Siap Saji', color: 'bg-blue-500' }
        ];

        const formatTime = (ts) => new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        const GUEST_COLORS = {
            'REGULER': 'bg-terminal-accent',
            'RESERVED': 'bg-terminal-warning',
            'MAJAR_PRIORITY': 'bg-blue-500',
            'MAJAR_OWNER': 'bg-purple-500'
        };

        return (
            <div className="flex w-full h-full bg-terminal-bg p-8 gap-8 overflow-hidden font-sans">
                {columns.map(col => (
                    <div key={col.id} className="flex-1 flex flex-col gap-6 h-full">
                        {/* Column Header */}
                        <div className="bg-terminal-panel rounded-2xl p-5 flex justify-between items-center border-b-4 border-terminal-border shadow-sm sticky top-0 z-10">
                            <h4 className="font-black text-xl uppercase tracking-widest text-terminal-text">{col.label}</h4>
                            <div className={`${col.color} text-white w-10 h-10 rounded-full flex items-center justify-center font-black text-lg shadow-sm`}>
                                {filteredOrders.filter(o => o.stage === col.id).length}
                            </div>
                        </div>
                        
                        {/* Column Content */}
                        <div className="flex-1 overflow-y-auto space-y-6 pr-2 custom-scrollbar">
                            {filteredOrders.filter(o => o.stage === col.id).length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-20 opacity-10">
                                    <i className="bi bi-card-checklist text-7xl"></i>
                                </div>
                            ) : (
                                filteredOrders.filter(o => o.stage === col.id).map(order => {
                                    const guestColor = GUEST_COLORS[order.guest_category] || 'bg-terminal-accent';
                                    let mergedNames = '';
                                    try {
                                        const mergedIds = JSON.parse(order.merged_table_ids || '[]');
                                        if (mergedIds.length > 0) {
                                            const names = mergedIds.map(id => {
                                                const t = tables.find(tbl => String(tbl.id) === String(id));
                                                return t ? t.name : id;
                                            });
                                            mergedNames = `(+ ${names.join(', ')})`;
                                        }
                                    } catch(e) {}

                                    return (
                                        <div key={order.id} className={`bg-terminal-panel border-l-[10px] rounded-[2rem] p-6 shadow-md transition-all hover:scale-[1.02] ${
                                            col.id === 'READY_FOR_KITCHEN' ? 'border-l-terminal-accent' : 
                                            col.id === 'COOKING' ? 'border-l-terminal-warning' : 'border-l-blue-500'
                                        }`}>
                                            <div className="flex justify-between items-start mb-4">
                                                <div>
                                                    <div className="text-3xl font-black tracking-tighter flex items-center gap-2 text-terminal-text">
                                                        Meja {order.table.name} 
                                                        <span className="text-sm text-terminal-muted font-bold">{mergedNames}</span>
                                                    </div>
                                                    <div className="mt-1 flex items-center gap-2">
                                                        <Badge color={guestColor}>{order.guest_category || 'REGULER'}</Badge>
                                                        <span className="text-[10px] font-black uppercase tracking-widest text-terminal-muted">{order.order_type === 'TAKE_AWAY' ? 'TAKE AWAY' : 'DINE IN'}</span>
                                                    </div>
                                                </div>
                                                <div className="text-terminal-muted text-xs font-mono font-bold bg-terminal-bg px-3 py-1 rounded-lg border border-terminal-border shadow-sm">{formatTime(order.created_at)}</div>
                                            </div>

                                            {order.guest_category === 'RESERVED' && order.reservation_name && (
                                                <div className="mb-4 p-3 bg-terminal-warning/5 border border-terminal-warning/20 rounded-xl">
                                                    <div className="text-[10px] font-black text-terminal-warning uppercase tracking-widest">Reservasi</div>
                                                    <div className="font-bold text-sm text-terminal-text">{order.reservation_name} ({order.reservation_code})</div>
                                                </div>
                                            )}
                                            
                                            <div className="space-y-4 mb-8">
                                                {order.items.map((item, idx) => (
                                                    <div key={idx} className="flex gap-4 items-start bg-terminal-bg/50 p-3 rounded-2xl border border-terminal-border">
                                                        <div className="bg-white text-terminal-accent w-12 h-12 rounded-xl flex items-center justify-center font-black text-2xl flex-shrink-0 shadow-sm border border-terminal-border">
                                                            {item.qty}
                                                        </div>
                                                        <div className="flex-1">
                                                            <div className="font-black text-xl text-terminal-text">{item.menu_name}</div>
                                                            {item.note && (
                                                                <div className="mt-2 bg-terminal-warning/10 text-terminal-warning px-4 py-1.5 rounded-xl text-sm font-black border border-terminal-warning/20 inline-flex items-center gap-2 shadow-sm">
                                                                    <i className="bi bi-info-circle-fill"></i> {item.note}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>

                                            <div className="pt-2">
                                                {col.id === 'READY_FOR_KITCHEN' && (
                                                    <button 
                                                        onClick={() => handleUpdateStatus(order.id, 'COOKING')}
                                                        className="w-full bg-terminal-accent text-white py-4 rounded-2xl font-black text-xl flex items-center justify-center gap-3 hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-terminal-accent/20"
                                                    >
                                                        <i className="bi bi-play-fill text-3xl"></i> MULAI MASAK
                                                    </button>
                                                )}
                                                {col.id === 'COOKING' && (
                                                    <button 
                                                        onClick={() => handleUpdateStatus(order.id, 'READY')}
                                                        className="w-full bg-terminal-accent text-white py-4 rounded-2xl font-black text-xl flex items-center justify-center gap-3 hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-terminal-accent/20"
                                                    >
                                                        <i className="bi bi-check-lg text-3xl"></i> TANDAI SIAP
                                                    </button>
                                                )}
                                                {col.id === 'READY' && (
                                                    <button 
                                                        onClick={() => handleUpdateStatus(order.id, 'DONE')}
                                                        className="w-full bg-terminal-bg border border-terminal-border text-terminal-text py-4 rounded-2xl font-black text-xl flex items-center justify-center gap-3 hover:bg-black/5 active:scale-95 transition-all shadow-sm"
                                                    >
                                                        <i className="bi bi-box-arrow-right text-3xl"></i> SELESAI
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    </div>
                ))}
                
                <style>{`
                    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
                    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
                    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
                    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }
                `}</style>
            </div>
        );
    };

    const headerRoot = ReactDOM.createRoot(document.getElementById('kitchen-header-root'));
    headerRoot.render(<KitchenHeader />);

    const mainRoot = ReactDOM.createRoot(document.getElementById('kitchen-root'));
    mainRoot.render(<KitchenTerminal />);
</script>
@endsection
