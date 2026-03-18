@extends('layouts.terminal')

@section('title', 'Pilih Peran Terminal')
@section('terminal_role', 'GATEKEEPER')

@section('content')
<div id="index-root" class="w-full h-full"></div>
@endsection

@section('extra_js')
<script type="text/babel">
    const { useState } = React;

    const TerminalIndex = () => {
        const [staff] = useState(@json($staff));
        const [user] = useState(@json($user));

        const handleQuickSwitch = async (username) => {
            if (!confirm(`Pindah ke akun ${username}?`)) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('login.post') }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            const userInput = document.createElement('input');
            userInput.type = 'hidden';
            userInput.name = 'username';
            userInput.value = username;
            form.appendChild(userInput);
            
            const passInput = document.createElement('input');
            passInput.type = 'hidden';
            passInput.name = 'password';
            passInput.value = (username === 'bambangbali') ? 'bali' : (username === 'admin' ? 'admin' : 'password');
            form.appendChild(passInput);
            
            document.body.appendChild(form);
            form.submit();
        };

        const roles = [
            { 
                id: 'waiter', 
                title: 'WAITER', 
                icon: 'bi-person-walking', 
                desc: 'Pilih meja, susun pesanan, dan kirim ke kasir untuk verifikasi.', 
                route: '{{ route('terminal.waiter') }}',
                color: 'bg-blue-500'
            },
            { 
                id: 'kasir', 
                title: 'KASIR', 
                icon: 'bi-cash-stack', 
                desc: 'Verifikasi pesanan, terima pembayaran, dan kirim tiket ke dapur.', 
                route: '{{ route('terminal.kasir') }}',
                color: 'bg-terminal-accent'
            },
            { 
                id: 'kitchen', 
                title: 'KITCHEN', 
                icon: 'bi-fire', 
                desc: 'Terima tiket lunas, kelola status masak, dan tandai selesai.', 
                route: '{{ route('terminal.kitchen') }}',
                color: 'bg-orange-500'
            }
        ];

        return (
            <div className="flex flex-col items-center justify-center w-full h-full bg-terminal-bg p-10 overflow-y-auto font-sans">
                {/* Main Heading */}
                <div className="text-center mb-16 max-w-3xl">
                    <h1 className="text-6xl font-black text-white mb-6 tracking-tighter">MODE TERMINAL</h1>
                    <p className="text-xl text-terminal-muted font-medium leading-relaxed uppercase tracking-[0.3em]">Antarmuka Operasional Layar Penuh</p>
                </div>

                {/* Role Cards Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-10 w-full max-w-7xl">
                    {roles.map(role => (
                        <a 
                            key={role.id}
                            href={role.route}
                            className="bg-terminal-panel border border-terminal-border rounded-[3.5rem] p-10 text-center flex flex-col items-center gap-8 hover:border-terminal-accent hover:-translate-y-4 transition-all duration-500 group shadow-[0_30px_60px_rgba(0,0,0,0.4)] no-underline text-terminal-text active:scale-95"
                        >
                            <div className={`w-28 h-24 rounded-full bg-terminal-bg flex items-center justify-center text-5xl text-terminal-accent group-hover:${role.color} group-hover:text-black transition-all duration-500 shadow-inner border border-terminal-border/50`}>
                                <i className={`bi ${role.icon}`}></i>
                            </div>
                            <div>
                                <h2 className="text-3xl font-black text-white mb-4 tracking-tight group-hover:text-terminal-accent transition-colors">{role.title}</h2>
                                <p className="text-terminal-muted font-medium leading-relaxed px-4">{role.desc}</p>
                            </div>
                            <div className="w-full bg-terminal-bg border border-terminal-border py-4 rounded-2xl font-black uppercase tracking-[0.2em] mt-auto group-hover:bg-terminal-accent group-hover:text-black transition-all duration-500 shadow-lg">
                                Pilih {role.title}
                            </div>
                        </a>
                    ))}
                </div>

                {/* Workflow Helper */}
                <div className="mt-20 w-full max-w-4xl bg-terminal-panel/30 border border-dashed border-terminal-border/50 rounded-[2.5rem] p-8 text-center backdrop-blur-sm">
                    <h3 className="text-xs font-black text-terminal-muted uppercase tracking-[0.5em] mb-8 opacity-50">Alur Kerja Gatekeeper</h3>
                    <div className="flex items-center justify-center gap-6 text-sm font-black">
                        <div className="bg-terminal-bg border border-terminal-border px-6 py-3 rounded-xl shadow-lg">1. WAITER DRAFT</div>
                        <i className="bi bi-arrow-right text-terminal-accent text-xl animate-pulse"></i>
                        <div className="bg-terminal-bg border border-terminal-border px-6 py-3 rounded-xl shadow-lg">2. KASIR LUNAS</div>
                        <i className="bi bi-arrow-right text-terminal-accent text-xl animate-pulse"></i>
                        <div className="bg-terminal-bg border border-terminal-border px-6 py-3 rounded-xl shadow-lg">3. KITCHEN MASAK</div>
                    </div>
                </div>

                {/* Staff Quick Switch Section */}
                <div className="mt-16 w-full max-w-4xl flex flex-col items-center gap-10">
                    <div className="bg-terminal-panel border border-terminal-border rounded-[3rem] p-10 w-full shadow-2xl">
                        <h4 className="text-[10px] font-black text-center text-terminal-muted uppercase tracking-[0.5em] mb-10 opacity-70">Ganti Staff Cepat (PIN-less)</h4>
                        <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
                            {staff.map(s => (
                                <div 
                                    key={s.id}
                                    className={`p-5 rounded-[1.5rem] border-2 cursor-pointer transition-all duration-300 active:scale-90 flex flex-col items-center justify-center text-center gap-1 ${s.id === user.id ? 'border-terminal-accent bg-terminal-accent/10 shadow-[0_0_20px_rgba(34,197,94,0.2)]' : 'border-terminal-border bg-terminal-bg hover:border-terminal-accent/50'}`}
                                    onClick={() => handleQuickSwitch(s.username)}
                                >
                                    <div className="font-black text-sm text-white truncate w-full">{s.name}</div>
                                    <div className="text-[9px] text-terminal-muted uppercase font-black tracking-widest">{s.role}</div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Global Actions */}
                    <div className="flex gap-6 mb-10">
                        <button 
                            onClick={() => window.toggleFullScreen()}
                            className="bg-terminal-accent text-black px-10 py-5 rounded-[1.5rem] font-black text-lg hover:opacity-90 active:scale-95 transition-all shadow-[0_20px_40px_rgba(34,197,94,0.3)] flex items-center gap-3"
                        >
                            <i className="bi bi-fullscreen text-2xl"></i> 
                            <span className="tracking-widest uppercase">Layar Penuh</span>
                        </button>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button 
                                type="submit"
                                className="bg-terminal-bg border-2 border-terminal-border text-terminal-text px-10 py-5 rounded-[1.5rem] font-black text-lg hover:bg-white/5 active:scale-95 transition-all flex items-center gap-3"
                            >
                                <i className="bi bi-box-arrow-right text-2xl"></i> 
                                <span className="tracking-widest uppercase">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        );
    };

    const root = ReactDOM.createRoot(document.getElementById('index-root'));
    root.render(<TerminalIndex />);
</script>
@endsection
