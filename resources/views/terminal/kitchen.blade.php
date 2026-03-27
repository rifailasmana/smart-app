@extends('layouts.terminal')

@section('title', 'Kitchen - Majar Signature')
@section('terminal_role', 'DAPUR')

@section('header_extra')
    <div class="flex items-center gap-6 border-l border-gray-700 pl-6">
        <div id="kitchen-header-root"></div>
    </div>
@endsection

@section('content')
    <div class="w-full h-full" id="kitchen-root"></div>
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
            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2 group cursor-pointer" onClick={() => setSoundEnabled(!soundEnabled)}>
                    <div className={`w-10 h-5 rounded-full relative transition-all duration-300 ${soundEnabled ? 'bg-orange-500 shadow-sm' : 'bg-gray-700'}`}>
                        <div className={`absolute top-0.5 w-4 h-4 bg-white rounded-full transition-all duration-300 ${soundEnabled ? 'left-5.5' : 'left-0.5'}`}></div>
                    </div>
                    <label className="text-[9px] font-black text-gray-400 uppercase tracking-widest cursor-pointer group-hover:text-gray-200 transition-colors">Sound</label>
                </div>
                <div className="flex items-center gap-1.5">
                    <div className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                    <span className="text-[9px] font-black uppercase tracking-widest text-gray-400">Live</span>
                </div>
            </div>
        );
    };

    const OrderTicket = ({ order, onUpdateStatus, onUpdateItemStatus }) => {
        const stageConfig = {
            'COOKING': { border: 'border-blue-500', bg: 'bg-blue-50/50', label: 'Proses Dapur' },
            'READY': { border: 'border-green-500', bg: 'bg-green-50/50', label: 'Siap Saji' },
            'SERVED': { border: 'border-purple-500', bg: 'bg-purple-50/50', label: 'Sudah Disajikan' }
        };
        const config = stageConfig[order.stage] || { border: 'border-orange-500', bg: 'bg-orange-50/50', label: 'Antrian' };

        return (
            <div className={`bg-white rounded-2xl border-l-8 shadow-sm flex flex-col h-full transition-all hover:shadow-md overflow-hidden ${config.border}`}>
                <div className="p-4 border-b border-gray-100 flex justify-between items-start bg-white">
                    <div className="min-w-0 flex-1">
                        <h3 className="text-xl font-black text-gray-900 tracking-tighter truncate">Meja {order.table?.name || 'TA'}</h3>
                        <p className="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-0.5 truncate">{order.customer_name}</p>
                    </div>
                    <div className="text-right flex-shrink-0 ml-3">
                        <div className="text-[9px] font-bold text-gray-400">{new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                        <div className="text-[8px] font-black text-orange-500 uppercase tracking-tighter mt-0.5">{order.order_type}</div>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar bg-gray-50/30">
                    {order.items.map((item, idx) => {
                        const itemStatusColors = {
                            'cooking': 'bg-blue-100 text-blue-600',
                            'ready': 'bg-green-100 text-green-600',
                            'served': 'bg-purple-100 text-purple-600',
                            'void': 'bg-red-100 text-red-600'
                        };
                        return (
                            <div key={idx} className="p-3 rounded-xl bg-white border border-gray-100 shadow-sm flex flex-col gap-2 group transition-all hover:border-orange-200">
                                <div className="flex gap-3 items-start">
                                    <div className="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center font-black text-base text-orange-500 border border-gray-100 group-hover:bg-orange-500 group-hover:text-white transition-all flex-shrink-0">
                                        {item.qty}
                                    </div>
                                    <div className="flex-1 min-w-0 pt-0.5">
                                        <div className="font-bold text-[13px] text-gray-900 leading-tight uppercase tracking-tight truncate">{item.menu_name}</div>
                                        {item.note && <div className="text-[10px] text-red-500 font-bold italic mt-0.5 leading-tight">Note: {item.note}</div>}
                                        <span className={`inline-block mt-1 text-[7px] font-black uppercase px-1 py-0.5 rounded ${itemStatusColors[item.status] || 'bg-gray-100'}`}>
                                            {item.status}
                                        </span>
                                    </div>
                                </div>
                                {order.stage === 'READY_FOR_KITCHEN' && (
                                    <button 
                                        onClick={() => onUpdateStatus(order.id, 'COOKING')}
                                        className="w-full py-2 bg-blue-500 text-white rounded-lg font-black text-[9px] uppercase tracking-widest shadow-md shadow-blue-500/20 active:scale-95 transition-all"
                                    >
                                        Masak
                                    </button>
                                )}
                                {item.status === 'cooking' && (
                                    <button 
                                        onClick={() => onUpdateItemStatus(order.id, item.id, 'ready', item.menu_name)}
                                        className="w-full py-2 bg-green-500 text-white rounded-lg font-black text-[9px] uppercase tracking-widest shadow-md shadow-green-500/20 active:scale-95 transition-all"
                                    >
                                        Ready to Serve
                                    </button>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    };

    const KitchenTerminal = () => {
        const [view, setView] = useState('KITCHEN'); // KITCHEN, ORDER_STATUS, ORDER_HISTORY
        const [orders, setOrders] = useState([]);
        const [isLoading, setIsLoading] = useState(false);
        const [toast, setToast] = useState(null);
        const [confirmAction, setConfirmAction] = useState(null); // { orderId, newStatus, label }

        const onShowToast = (message, type = 'success') => {
            setToast({ message, type });
        };

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

        const handleUpdateStatus = async () => {
            if (!confirmAction) return;
            const { orderId, newStatus } = confirmAction;
            
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
                if (response.ok) {
                    onShowToast(`Status pesanan berhasil diupdate!`);
                    fetchOrders();
                } else {
                    onShowToast('Gagal mengupdate status', 'error');
                }
            } catch (e) {
                onShowToast('Terjadi kesalahan sistem', 'error');
            } finally {
                setIsLoading(false);
                setConfirmAction(null);
            }
        };

        const handleUpdateItemStatus = async (orderId, itemId, newStatus, menuName) => {
            try {
                const response = await fetch(`/terminal/orders/${orderId}/items/${itemId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                if (response.ok) {
                    onShowToast(`${menuName} siap disajikan!`);
                    fetchOrders();
                } else {
                    onShowToast('Gagal mengupdate item', 'error');
                }
            } catch (e) {
                onShowToast('Terjadi kesalahan sistem', 'error');
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
                <div className="flex-1 h-full bg-gray-100 p-6 flex gap-6 overflow-x-auto custom-scrollbar">
                    {columns.map(col => (
                        <div key={col.id} className="flex-1 flex flex-col h-full min-w-[300px] max-w-[400px]">
                            <div className="flex justify-between items-center mb-4 px-2">
                                <h4 className="text-lg font-black text-gray-900 uppercase tracking-tighter">{col.label}</h4>
                                <div className={`${col.color} text-white px-3 py-0.5 rounded-full font-black text-xs shadow-sm`}>
                                    {orders.filter(o => o.stage === col.id).length}
                                </div>
                            </div>

                            <div className="flex-1 overflow-y-auto space-y-4 pr-1 custom-scrollbar">
                                {orders.filter(o => o.stage === col.id).length === 0 ? (
                                    <div className="h-full flex flex-col items-center justify-center opacity-10 py-10">
                                        <i className="bi bi-card-checklist text-5xl"></i>
                                    </div>
                                ) : (
                                    orders.filter(o => o.stage === col.id).map(order => (
                                        <OrderTicket
                                            key={order.id}
                                            order={order}
                                            onUpdateStatus={(id, status) => {
                                                const labels = {
                                                    'COOKING': 'Mulai masak?',
                                                    'READY': 'Sudah siap?',
                                                    'DONE': 'Selesai?'
                                                };
                                                setConfirmAction({ orderId: id, newStatus: status, label: labels[status] });
                                            }}
                                            onUpdateItemStatus={handleUpdateItemStatus}
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
                <div className="w-20 bg-[#063024] flex flex-col border-r border-[#063024] flex-shrink-0">
                    <div className="p-4 border-b border-[#063024]">
                        <div className="w-full aspect-square rounded-xl bg-gradient-to-br from-orange-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-orange-500/30">
                            <span className="font-black text-xl text-white">S</span>
                        </div>
                    </div>
                    <div className="flex-1 py-2 overflow-y-auto no-scrollbar">
                        <SidebarIcon icon="bi-fire" label="Dapur" active={view === 'KITCHEN'} onClick={() => setView('KITCHEN')} />
                        <SidebarIcon icon="bi-list-check" label="Status" active={view === 'ORDER_STATUS'} onClick={() => setView('ORDER_STATUS')} />
                        <SidebarIcon icon="bi-clock-history" label="History" active={view === 'ORDER_HISTORY'} onClick={() => setView('ORDER_HISTORY')} />
                    </div>
                    <div className="py-2 border-t border-[#063024]">
                        <SidebarIcon icon="bi-gear-fill" label="Setting" />
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1 h-full overflow-hidden bg-[#daaa68]">
                    {renderView()}
                </div>

                {confirmAction && (
                    <ConfirmModal
                        title="Update Status?"
                        message={confirmAction.label}
                        onConfirm={handleUpdateStatus}
                        onClose={() => setConfirmAction(null)}
                        confirmText="Ya, Update"
                    />
                )}
                {toast && <Toast message={toast.message} type={toast.type} onClose={() => setToast(null)} />}
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
        .fluid-text-h1 {
            font-size: clamp(1.25rem, 3vw, 2rem);
        }
        .fluid-text-h2 {
            font-size: clamp(1rem, 2.5vw, 1.5rem);
        }
        .fluid-text-body {
            font-size: clamp(0.75rem, 2vw, 0.875rem);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 10px;
        }
    </style>
@endsection
