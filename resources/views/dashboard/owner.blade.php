@extends('layouts.dashboard')

@section('title', 'Majar Signature | Owner Dashboard')
@section('header_title', 'Owner Command Center')
@section('header_subtitle', 'Full control, analytics, and high-level decision making')

@section('content')
<div class="container-fluid py-4">
    @if($tab === 'dashboard')
        <!-- 🏠 1. DASHBOARD OWNER -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-brand">
                    <div class="small fw-bold text-dark opacity-75 uppercase">Revenue Today</div>
                    <h2 class="fw-black mb-1">Rp {{ number_format($todaySales, 0, ',', '.') }}</h2>
                    @php 
                        $diff = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : ($todaySales > 0 ? 100 : 0);
                    @endphp
                    <div class="small {{ $diff >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                        <i class="fas fa-arrow-{{ $diff >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($diff), 1) }}% vs Yesterday
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="small text-muted fw-bold uppercase">Estimated Profit (Today)</div>
                    <h2 class="fw-black mb-1 text-success">Rp {{ number_format($todayProfit, 0, ',', '.') }}</h2>
                    <div class="small text-muted">Based on HPP calculation</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="small text-muted fw-bold uppercase">Transactions Today</div>
                    <h2 class="fw-black mb-1 text-dark">{{ $todayTransactions }}</h2>
                    <div class="small text-muted">Completed orders</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-danger-soft border-start border-danger border-4">
                    <div class="small text-danger fw-bold uppercase">Critical Inventory</div>
                    <h2 class="fw-black mb-1 text-danger">{{ $lowStockCount }} <small class="fs-6">Items</small></h2>
                    <div class="small text-muted">Need immediate restock</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h6 class="fw-bold mb-4">Sales Analytics (Last 7 Days)</h6>
                    <div style="height: 300px; display: flex; align-items: flex-end; gap: 10px;">
                        @foreach($salesAnalytics->take(-7) as $data)
                            @php $max = $salesAnalytics->max('revenue') ?: 1; $height = ($data->revenue / $max) * 100; @endphp
                            <div class="flex-grow-1 d-flex flex-column align-items-center">
                                <div class="bg-brand rounded-top w-100" style="height: {{ $height }}%;" title="Rp {{ number_format($data->revenue, 0) }}"></div>
                                <div class="small mt-2" style="font-size: 0.7rem;">{{ date('d/m', strtotime($data->date)) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h6 class="fw-bold mb-4">Best Selling (This Month)</h6>
                    <div class="list-group list-group-flush">
                        @foreach($bestSelling as $menu)
                        <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold">{{ $menu->menu_name }}</span>
                            <span class="badge bg-light text-dark border rounded-pill px-3">{{ $menu->total_qty }} Sold</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    @elseif($tab === 'analytics')
        <!-- 📊 2. SALES & PROFIT ANALYTICS -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Deep Sales & Profit Analytics</h5>
                <div class="d-flex gap-2">
                    <input type="month" class="form-control rounded-pill" value="{{ date('Y-m') }}">
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="p-4 bg-light rounded-4">
                            <div class="small text-muted uppercase fw-bold mb-2">Total Revenue (Month)</div>
                            <h3 class="fw-black mb-0">Rp {{ number_format($monthSales, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 bg-success-soft rounded-4">
                            <div class="small text-success uppercase fw-bold mb-2">Total Gross Profit</div>
                            <h3 class="fw-black mb-0 text-success">Rp {{ number_format($monthSales * 0.4, 0, ',', '.') }}</h3>
                            <small class="text-muted italic">Est. 40% Margin</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 bg-primary-soft rounded-4">
                            <div class="small text-primary uppercase fw-bold mb-2">Avg. Ticket Size</div>
                            <h3 class="fw-black mb-0 text-primary">Rp {{ number_format($monthSales / ($todayTransactions ?: 1), 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
                <p class="text-center py-5 text-muted italic">Advanced charts (Chart.js) implementation recommended for production visualization.</p>
            </div>
        </div>

    @elseif($tab === 'inventory')
        <!-- 📦 3. INVENTORY INSIGHT -->
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-white py-4 px-4 border-0">
                        <h5 class="mb-0 fw-bold">Most Consumed Ingredients</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Ingredient Name</th>
                                    <th class="text-center">Total Usage</th>
                                    <th class="text-end pe-4">Stock Value Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mostUsedIngredients as $ing)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $ing->name }}</td>
                                    <td class="text-center">{{ number_format($ing->total_usage, 2) }} {{ $ing->unit }}</td>
                                    <td class="text-end pe-4 text-danger">High Impact</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-dark text-white">
                    <h5 class="fw-bold mb-4">Total Inventory Value</h5>
                    <h2 class="fw-black text-warning">Rp {{ number_format($inventoryValue, 0, ',', '.') }}</h2>
                    <p class="small opacity-75">Current capital tied up in stock across all categories.</p>
                    <hr class="opacity-25">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Raw Ingredients</span>
                        <span class="fw-bold">85%</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: 85%"></div>
                    </div>
                </div>
            </div>
        </div>

    @elseif($tab === 'approval')
        <!-- ✅ 5. APPROVAL PANEL -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold mb-4"><i class="fas fa-money-check-alt me-2 text-primary"></i> Payroll Approval</h5>
                    <div class="text-center py-4">
                        <h2 class="fw-black">{{ $pendingPayroll }}</h2>
                        <p class="text-muted">Drafts pending for this period</p>
                        <a href="{{ route('dashboard.hrd') }}?tab=payroll" class="btn btn-primary px-4 rounded-pill fw-bold">Review & Approve</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold mb-4"><i class="fas fa-boxes me-2 text-warning"></i> Restock Approval</h5>
                    <div class="text-center py-4">
                        <h2 class="fw-black">{{ $pendingRestock }}</h2>
                        <p class="text-muted">Warehouse requests pending</p>
                        <a href="{{ route('dashboard.inventory') }}?tab=request" class="btn btn-brand px-4 rounded-pill fw-bold">Process Requests</a>
                    </div>
                </div>
            </div>
        </div>

    @elseif($tab === 'employees')
        <!-- 👥 4. MANAGEMENT KARYAWAN -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0">
                <h5 class="mb-0 fw-bold">Employee Control List</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                <small class="text-muted">{{ $emp->email }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper($emp->role) }}</span></td>
                            <td><span class="badge bg-success rounded-pill px-3">ACTIVE</span></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3">Disable Access</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($tab === 'menu')
        <!-- 🍽️ 7. MENU & PRICING CONTROL -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Menu Pricing & Status Control</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Menu Name</th>
                            <th>Category</th>
                            <th>Price (IDR)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Quick Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menuItems as $item)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $item->name }}</td>
                            <td><span class="small text-muted">{{ $item->category }}</span></td>
                            <td>
                                <form action="{{ route('owner.menu.price', $item->id) }}" method="POST" class="d-flex align-items-center gap-2">
                                    @csrf
                                    <input type="number" name="price" class="form-control form-control-sm rounded-pill px-3 w-75" value="{{ $item->price }}">
                                    <button class="btn btn-sm btn-dark rounded-circle"><i class="fas fa-check"></i></button>
                                </form>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 {{ $item->active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $item->active ? 'AVAILABLE' : 'OUT OF STOCK' }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <form action="{{ route('owner.menu.toggle', $item->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm {{ $item->active ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill px-3">
                                        {{ $item->active ? 'Set Unavailable' : 'Set Available' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($tab === 'coupon')
        <!-- 🎟️ 8. PROMO (Voucher) -->
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold mb-4">Buat Promo (Voucher)</h5>
                    <form action="{{ route('manager.coupon.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kode Promo</label>
                            <input type="text" name="code" class="form-control rounded-3" placeholder="e.g. OWNER_EXCLUSIVE" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Diskon (%)</label>
                            <input type="number" name="value" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Kategori Target</label>
                            <select name="category_restriction" class="form-select rounded-3">
                                <option value="Regular">Regular</option>
                                <option value="Reservation">Reservation</option>
                                <option value="Majar Priority">Majar Priority</option>
                                <option value="Majar Signature">Majar Signature</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-3 rounded-3 fw-bold">BUAT PROMO</button>
                    </form>
                </div>
            </div>
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold">Daftar Promo & Tracking</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Code</th>
                                    <th>Segment</th>
                                    <th>Dipakai</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($coupons as $c)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $c->code }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $c->category_restriction }}</span></td>
                                    <td>{{ $c->is_used ? 'Ya' : 'Belum' }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge {{ $c->is_used ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success' }} rounded-pill px-3">
                                            {{ $c->is_used ? 'EXPIRED' : 'ACTIVE' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @elseif($tab === 'settings')
        <!-- ⚙️ 10. SETTINGS -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-4">System & Branding Settings</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Restaurant Name</label>
                        <input type="text" class="form-control rounded-3" value="Majar Signature Bali">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tax Rate (%)</label>
                        <input type="number" class="form-control rounded-3" value="10">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Service Charge (%)</label>
                        <input type="number" class="form-control rounded-3" value="5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Currency Symbol</label>
                        <input type="text" class="form-control rounded-3" value="IDR">
                    </div>
                </div>
            </div>
            <div class="mt-4 text-end">
                <button class="btn btn-dark px-4 rounded-pill fw-bold">Save Settings</button>
            </div>
        </div>
    @endif
</div>

<style>
    .bg-brand { background: var(--brand-gradient); color: #000; }
    .bg-success-soft { background-color: rgba(34, 197, 94, 0.1); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
    .bg-warning-soft { background-color: rgba(251, 191, 36, 0.1); }
    .bg-primary-soft { background-color: rgba(59, 130, 246, 0.1); }
    .text-orange { color: #FF8C00; }
    .fw-black { font-weight: 900; }
    .fw-bold { font-weight: 700; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
    .italic { font-style: italic; }
</style>
@endsection
