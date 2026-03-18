@extends('layouts.dashboard')

@section('title', 'Admin Diagnostics - Majar Signature')
@section('header_title', 'Admin Diagnostics')
@section('header_subtitle', 'Deteksi route dan function yang terpakai di semua view')

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="fw-bold">Ringkasan</div>
                        <div class="text-muted small">
                            Total routes: <span class="badge badge-brand">{{ $routes->count() }}</span>
                            &nbsp;•&nbsp; View files dengan function: <span class="badge badge-brand">{{ $viewFunctions->count() }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-brand btn-sm" href="{{ route('admin.warungs') }}">Kembali</a>
                    </div>
                </div>

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-routes" type="button" role="tab">Route All User</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-functions" type="button" role="tab">Function All View</button>
                    </li>
                </ul>

                <div class="tab-content pt-4">
                    <div class="tab-pane fade show active" id="tab-routes" role="tabpanel">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-lg-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control bg-light border-0" id="routeSearch" placeholder="Cari route: name / uri / action / middleware">
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <select class="form-select" id="roleFilter">
                                    <option value="">Semua role-middleware</option>
                                    @foreach($routesByRole as $roleKey => $items)
                                        <option value="{{ $roleKey }}">{{ $roleKey }} ({{ count($items) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="routesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 110px;">Method</th>
                                        <th>URI</th>
                                        <th>Name</th>
                                        <th>Action</th>
                                        <th>Middleware</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($routes as $r)
                                    @php
                                        $roleMw = collect($r['middleware'])->first(fn($m) => is_string($m) && str_starts_with($m, 'role:'));
                                        $roleKey = $roleMw ?: 'no-role-middleware';
                                    @endphp
                                    <tr data-role="{{ $roleKey }}">
                                        <td><span class="badge bg-dark">{{ $r['methods'] }}</span></td>
                                        <td class="fw-bold text-dark">{{ $r['uri'] }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $r['name'] ?: '-' }}</span></td>
                                        <td class="small text-muted">{{ $r['action'] }}</td>
                                        <td class="small">
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($r['middleware'] as $m)
                                                    <span class="badge bg-light text-dark border">{{ $m }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-functions" role="tabpanel">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-lg-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control bg-light border-0" id="fnSearch" placeholder="Cari nama file atau function()">
                                </div>
                            </div>
                            <div class="col-12 col-lg-6 text-muted small d-flex align-items-center">
                                Sumber: scan Blade view (pattern: <span class="badge bg-light text-dark border">function nama()</span>)
                            </div>
                        </div>

                        <div class="accordion" id="fnAccordion">
                            @foreach($viewFunctions as $idx => $vf)
                                <div class="accordion-item border-0 mb-2">
                                    <h2 class="accordion-header" id="fnHeading{{ $idx }}">
                                        <button class="accordion-button collapsed rounded-4 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#fnCollapse{{ $idx }}">
                                            <span class="fw-bold text-dark me-2">{{ $vf['file'] }}</span>
                                            <span class="badge badge-brand">{{ count($vf['functions']) }} functions</span>
                                        </button>
                                    </h2>
                                    <div id="fnCollapse{{ $idx }}" class="accordion-collapse collapse" data-bs-parent="#fnAccordion">
                                        <div class="accordion-body bg-white rounded-4 border mt-2">
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($vf['functions'] as $fn)
                                                    <span class="badge bg-light text-dark border fn-chip">{{ $fn }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const routeSearch = document.getElementById('routeSearch');
        const roleFilter = document.getElementById('roleFilter');
        const routesTable = document.getElementById('routesTable');

        function filterRoutes() {
            if (!routesTable) return;
            const q = (routeSearch?.value || '').toLowerCase().trim();
            const role = (roleFilter?.value || '').trim();
            const rows = routesTable.querySelectorAll('tbody tr');
            rows.forEach((row) => {
                const text = row.innerText.toLowerCase();
                const roleMatch = !role || (row.getAttribute('data-role') === role);
                const textMatch = !q || text.includes(q);
                row.style.display = (roleMatch && textMatch) ? '' : 'none';
            });
        }

        if (routeSearch) routeSearch.addEventListener('input', filterRoutes);
        if (roleFilter) roleFilter.addEventListener('change', filterRoutes);
    })();

    (function () {
        const input = document.getElementById('fnSearch');
        const accordion = document.getElementById('fnAccordion');
        if (!input || !accordion) return;

        input.addEventListener('input', function () {
            const q = (input.value || '').toLowerCase().trim();
            const items = accordion.querySelectorAll('.accordion-item');
            items.forEach((item) => {
                const text = item.innerText.toLowerCase();
                item.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    })();
</script>
@endsection

