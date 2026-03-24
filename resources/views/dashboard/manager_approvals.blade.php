@extends('layouts.dashboard')

@section('title', 'Manager Approvals')
@section('header_title', 'Approval Center')
@section('header_subtitle', 'Manage approval requests')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Approval Requests</h4>
            <a class="btn btn-primary" href="{{ route('manager.approvals.create') }}">Create Request</a>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($approvals as $a)
                            <tr>
                                <td>{{ $a->id }}</td>
                                <td>{{ strtoupper($a->type) }}</td>
                                <td>{{ strtoupper($a->status) }}</td>
                                <td>{{ $a->requester ? $a->requester->name : '-' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('manager.approvals.edit', $a->id) }}">Edit</a>
                                    <form class="d-inline-block" action="{{ route('manager.approvals.destroy', $a->id) }}"
                                        method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $approvals->links() }}
            </div>
        </div>
    </div>
@endsection
