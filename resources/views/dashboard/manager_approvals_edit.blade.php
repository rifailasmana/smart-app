@extends('layouts.dashboard')

@section('title', 'Edit Approval Request')
@section('header_title', 'Edit Approval')
@section('header_subtitle', 'Update approval request')

@section('content')
    <div class="container-fluid py-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <form action="{{ route('manager.approvals.update', $approval->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type</label>
                    <input class="form-control" name="type" type="text" value="{{ $approval->type }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Status</label>
                    <select class="form-select" name="status">
                        <option value="pending" {{ $approval->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $approval->status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $approval->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason</label>
                    <input class="form-control" name="reason" type="text" value="{{ $approval->reason }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Payload (JSON)</label>
                    <textarea class="form-control" name="payload" rows="6">{{ json_encode($approval->payload) }}</textarea>
                </div>
                <button class="btn btn-primary">Save</button>
            </form>
            <div class="mt-3">
                <form class="d-inline-block" action="{{ route('manager.approvals.process', $approval->id) }}"
                    method="POST">
                    @csrf
                    <input name="action" type="hidden" value="approve">
                    <button class="btn btn-success">Approve</button>
                </form>
                <form class="d-inline-block" action="{{ route('manager.approvals.process', $approval->id) }}"
                    method="POST">
                    @csrf
                    <input name="action" type="hidden" value="reject">
                    <button class="btn btn-danger">Reject</button>
                </form>
            </div>
        </div>
    </div>
@endsection
