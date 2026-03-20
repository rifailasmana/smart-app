@extends('layouts.terminal')

@section('title', 'Kitchen - Majar Signature')
@section('terminal_role', 'DAPUR')

@section('header_extra')
<div class="flex items-center gap-6 border-l border-gray-700 pl-6">
    <div id="kitchen-header-root"></div>
</div>
@endsection

@section('content')
<div id="kitchen-root" class="w-full h-full"></div>
@endsection

@section('extra_js')
<script type="text/babel">
    const { useState, useEffect, useMemo, useCallback } = React;

    // --- Components ---

    const SidebarIcon = ({ icon, label, active = false }) => (
        <div className={`relative flex flex-col items-center justify-center w-full py-5 cursor-pointer transition-all duration-200 group ${active ? 'text-orange-500' : 'text-gray-500 hover:text-orange-400'}`}>
            {active && <div className="absolute left-0 top-0 bottom-0 w-1.5 bg-orange-500 rounded-r-full shadow-[2px_0_10px_rgba(249,115,22,0.4)]"></div>}
            <div className={`p-2 rounded-xl transition-all ${active ? 'bg-orange-500/10' : 'group-hover:bg-gray-800'}`}>
                <i className={`bi ${icon} text-2xl`}></i>
            </div>
            <span className="text-[10px] font-black uppercase tracking-tighter mt-1">{label}</span>
        </div>
    );

    const KitchenHeader = () => {
        const [soundEnabled, setSoundEnabled] = useState(true);

        return (
            <div className="flex items-center gap-6">
                <div className="flex items-center gap-3 group cursor-pointer" onClick={() => setSoundEnabled(!soundEnabled)}>
                    <div className={`w-12 h-6 rounded-full relative transition-all duration-300 ${soundEnabled ? 'bg-orange-500 shadow-[0_0_10px_rgba(249,115,22,0.3)]' : 'bg-gray-700'}`}>
                        <div className={`absolute top-1 w-4 h-4 bg-white rounded-full transition-all duration-300 shadow-sm ${soundEnabled ? 'left-7' : 'left-1'}`}></div>
                    </div>
                    <label className="text-[10px] font-black text-gray-400 uppercase tracking-widest cursor-pointer group-hover:text-gray-200 transition-colors">Suara Notif</label>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span className="text-[10px] font-black uppercase tracking-widest text-gray-400">Live Update</span>
                </div>
            </div>
        );
    };

    const OrderTicket = ({ order, onUpdateStatus }) => {
        const stageConfig = {
            'READY_FOR_KITCHEN': { border: 'border-orange-500', bg: 'bg-orange-50/50', label: 'Antrian Baru', btn: 'Mulai Masak', btnColor: 'bg-orange-500' },
            'COOKING': { border: 'border-blue-500', bg: 'bg-blue-50/50', label: 'Sedang Dimasak', btn: 'Tandai Siap', btnColor: 'bg-blue-500' },
            'READY': { border: 'border-green-500', bg: 'bg-green-50/50', label: 'Siap Saji', btn: 'Selesai', btnColor: 'bg-green-500' }
        };
        const config = stageConfig[order.stage] || stageConfig.READY_FOR_KITCHEN;

        return (
            <div className={`bg-white rounded-[2.5rem] border-l-[12px] shadow-lg flex flex-col h-full transition-all hover:scale-[1.02] hover:shadow-2xl overflow-hidden ${config.border}`}>
                <div className="p-6 border-b border-gray-100 flex justify-between items-start">
                    <div>
                        <h3 className="text-3xl font-black text-gray-900 tracking-tighter">Meja {order.table?.name || 'TA'}</h3>
                        <p className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mt-1">#{order.code}</p>
                    </div>
                    <div className="text-right">
                        <div className="px-3 py-1 rounded-full bg-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">
                            {order.order_type}
                        </div>
                        <div className="text-[10px] font-bold text-gray-400">{new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                    {order.items.map((item, idx) => (
                        <div key={idx} className="flex gap-4 items-start group">
                            <div className="w-12 h-12 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center font-black text-2xl text-orange-500 shadow-sm group-hover:bg-orange-500 group-hover:text-white transition-all">
                                {item.qty}
                            </div>
                            <div className="flex-1 pt-1">
                                <div className="font-black text-xl text-gray-900 leading-tight uppercase tracking-tight">{item.menu_name}</div>
                                {item.note && (
                                    <div className="mt-2 p-3 rounded-2xl bg-orange-50 border border-orange-100 text-orange-700 text-xs font-bold flex items-center gap-2">
                                        <i className="bi bi-info-circle-fill"></i> {item.note}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="p-6 bg-gray-50/50 border-t border-gray-100">
                    <button 
                        onClick={() => onUpdateStatus(order.id, order.stage === 'READY_FOR_KITCHEN' ? 'COOKING' : order.stage === 'COOKING' ? 'READY' : 'DONE')}
                        className={`w-full py-5 ${config.btnColor} text-white font-black rounded-[1.5rem] transition-all active:scale-95 shadow-lg text-lg uppercase tracking-widest hover:opacity-90`}
                    >
                        {config.btn}
                    </button>
                </div>
            </div>
        );
    };

    const KitchenTerminal = () => {
        const [view, setView] = useState('KITCHEN'); // KITCHEN, ORDER_STATUS, ORDER_HISTORY
        const [orders, setOrders] = useState([]);
        const [isLoading, setIsLoading] = useState(false);

        const fetchOrders = useCallback(async () => {
            try {
                const response = await fetch('/terminal/orders?role=kitchen', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to fetch orders');
                const data = await response.json();
                setOrders(data);
            } catch (e) {
                console.error('Failed to fetch kitchen orders', e);
            }
        }, []);

        useEffect(() => {
            fetchOrders();
            const interval = setInterval(fetchOrders, 5000);
            return () => clearInterval(interval);
        }, [fetchOrders]);

        const handleUpdateStatus = async (orderId, newStatus) => {
            setIsLoading(true);
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
            } catch (e) {
                alert('Gagal mengupdate status: ' + e.message);
            } finally {
                setIsLoading(false);
            }
        };

        const columns = [
            { id: 'READY_FOR_KITCHEN', label: 'Pesanan Baru', color: 'bg-orange-500' },
            { id: 'COOKING', label: 'Proses Masak', color: 'bg-blue-500' },
            { id: 'READY', label: 'Siap Saji', color: 'bg-green-500' }
        ];

        const renderView = () => {
            if (view === 'ORDER_HISTORY') {
                return <OrderHistoryView onBack={() => setView('KITCHEN')} />;
            }
            if (view === 'ORDER_STATUS') {
                return <OrderStatusView role="kitchen" onBack={() => setView('KITCHEN')} />;
            }

            return (
                <div className="flex-1 h-full bg-gray-50 p-8 flex gap-8 overflow-hidden">
                    {columns.map(col => (
                        <div key={col.id} className="flex-1 flex flex-col h-full min-w-[340px]">
                            <div className="flex justify-between items-center mb-6">
                                <h4 className="text-xl font-black text-gray-900 uppercase tracking-tighter">{col.label}</h4>
                                <div className={`${col.color} text-white px-4 py-1 rounded-full font-black text-sm shadow-md`}>
                                    {orders.filter(o => o.stage === col.id).length}
                                </div>
                            </div>
                            
                            <div className="flex-1 overflow-y-auto space-y-6 pr-2 custom-scrollbar">
                                {orders.filter(o => o.stage === col.id).length === 0 ? (
                                    <div className="h-full flex flex-col items-center justify-center opacity-10 py-20">
                                        <i className="bi bi-card-checklist text-7xl"></i>
                                    </div>
                                ) : (
                                    orders.filter(o => o.stage === col.id).map(order => (
                                        <OrderTicket 
                                            key={order.id} 
                                            order={order} 
                                            onUpdateStatus={handleUpdateStatus} 
                                        />
                                    ))
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            );
        };

        return (
            <div className="w-full h-full flex overflow-hidden">
                {/* Fixed Sidebar */}
                <div className="w-24 bg-gray-900 flex flex-col border-r border-gray-800">
                    <div className="p-6 border-b border-gray-800">
                        <div className="w-full aspect-square rounded-2xl bg-gradient-to-br from-orange-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-orange-500/30">
                            <span className="font-black text-2xl text-white">S</span>
                        </div>
                    </div>
                    <div className="flex-1 py-4">
                        <SidebarIcon icon="bi-fire" label="Dapur" active={view === 'KITCHEN'} onClick={() => setView('KITCHEN')} />
                        <SidebarIcon icon="bi-list-check" label="Status" active={view === 'ORDER_STATUS'} onClick={() => setView('ORDER_STATUS')} />
                        <SidebarIcon icon="bi-clock-history" label="History" active={view === 'ORDER_HISTORY'} onClick={() => setView('ORDER_HISTORY')} />
                    </div>
                    <div className="py-4 border-t border-gray-800">
                        <SidebarIcon icon="bi-gear-fill" label="Setting" />
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1 h-full overflow-hidden">
                    {renderView()}
                </div>
            </div>
        );
    };

    // --- Reuse Status & History Components ---
    // (Note: In a real project these should be in shared JS files, but for blade we duplicate or use a shared layout)
    
    const OrderStatusView = ({ role, onBack }) => {
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

        const getBadgeClass = (stage) => {
            switch(stage) {
                case 'WAITING_CASHIER': return 'bg-yellow-100 text-yellow-600';
                case 'READY_FOR_KITCHEN': return 'bg-orange-100 text-orange-600';
                case 'COOKING': return 'bg-blue-100 text-blue-600';
                case 'READY': return 'bg-green-100 text-green-600';
                case 'SERVED': return 'bg-purple-100 text-purple-600';
                default: return 'bg-gray-100 text-gray-600';
            }
        };

        return (
            <div className="w-full h-full flex flex-col p-8 bg-gray-50 animate-in fade-in duration-500 overflow-hidden">
                <div className="flex items-center gap-4 mb-8">
                    <button onClick={onBack} className="text-gray-400 hover:text-gray-900"><i className="bi bi-arrow-left text-2xl"></i></button>
                    <h1 className="text-3xl font-black text-gray-900 tracking-tighter">Status Monitoring</h1>
                </div>

                <div className="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start custom-scrollbar pr-2">
                    {orders.map(order => (
                        <div key={order.id} className="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 flex flex-col">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h4 className="font-black text-gray-900">Meja {order.table?.name || 'TA'}</h4>
                                    <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">#{order.code}</p>
                                </div>
                                <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${getBadgeClass(order.stage)}`}>
                                    {order.stage.replace(/_/g, ' ')}
                                </span>
                            </div>
                            <div className="flex-1 space-y-2">
                                {order.items.map((item, idx) => (
                                    <div key={idx} className="flex justify-between text-sm">
                                        <span className="text-gray-600 font-medium">{item.qty}x {item.menu_name}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    };

    const OrderHistoryView = ({ onBack }) => {
        const [history, setHistory] = useState([]);
        useEffect(() => {
            fetch('/terminal/orders/history').then(res => res.json()).then(setHistory);
        }, []);

        return (
            <div className="w-full h-full flex flex-col p-8 bg-gray-50 animate-in fade-in duration-500 overflow-hidden">
                <div className="flex items-center gap-4 mb-8">
                    <button onClick={onBack} className="text-gray-400 hover:text-gray-900"><i className="bi bi-arrow-left text-2xl"></i></button>
                    <h1 className="text-3xl font-black text-gray-900 tracking-tighter">History Dapur</h1>
                </div>
                <div className="flex-1 overflow-y-auto bg-white rounded-[2rem] border border-gray-100 shadow-sm custom-scrollbar">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="border-b border-gray-50">
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Waktu</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Kode</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Meja</th>
                                <th className="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {history.map(order => (
                                <tr key={order.id} className="border-b border-gray-50">
                                    <td className="p-6 text-sm font-bold text-gray-600">{new Date(order.created_at).toLocaleTimeString()}</td>
                                    <td className="p-6 text-sm font-black text-gray-900">{order.code}</td>
                                    <td className="p-6 text-sm font-bold text-gray-600">{order.table?.name || 'TA'}</td>
                                    <td className="p-6"><span className="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-green-100 text-green-600">{order.stage}</span></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        );
    };

    const headerRoot = ReactDOM.createRoot(document.getElementById('kitchen-header-root'));
    headerRoot.render(<KitchenHeader />);

    const root = ReactDOM.createRoot(document.getElementById('kitchen-root'));
    root.render(<KitchenTerminal />);
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
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
</style>
@endsection
