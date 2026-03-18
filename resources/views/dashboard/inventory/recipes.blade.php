@extends('layouts.app')

@section('title', 'Manajemen Resep: ' . $menuItem->name)

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1">Resep: {{ $menuItem->name }}</h4>
                        <p class="text-muted mb-0">Tentukan bahan baku yang digunakan untuk setiap porsi menu ini.</p>
                    </div>
                    <a href="{{ route('dashboard.inventory') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Inventori
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Tambah Bahan -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Tambah Bahan Baku</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.recipes.store', $menuItem->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pilih Bahan</label>
                            <select name="ingredient_id" class="form-select select2" required>
                                <option value="">-- Pilih Bahan --</option>
                                @foreach($ingredients as $ingredient)
                                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kuantitas per Porsi</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" name="quantity" class="form-control" placeholder="Contoh: 0.25" required>
                                <span class="input-group-text">Satuan</span>
                            </div>
                            <div class="form-text">Masukkan jumlah yang digunakan untuk 1 porsi menu ini.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg"></i> Tambahkan ke Resep
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Bahan dalam Resep -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Daftar Bahan Resep</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nama Bahan</th>
                                    <th class="text-center">Jumlah / Porsi</th>
                                    <th class="text-center">Satuan</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recipes as $recipe)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $recipe->ingredient->name }}</div>
                                            <div class="small text-muted">Stok saat ini: {{ $recipe->ingredient->stock }} {{ $recipe->ingredient->unit }}</div>
                                        </td>
                                        <td class="text-center fw-bold">{{ $recipe->quantity }}</td>
                                        <td class="text-center">{{ $recipe->ingredient->unit }}</td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('inventory.recipes.destroy', $recipe->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus bahan ini dari resep?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-journal-x display-4 mb-3"></i>
                                            <p>Belum ada bahan yang ditambahkan ke resep ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
