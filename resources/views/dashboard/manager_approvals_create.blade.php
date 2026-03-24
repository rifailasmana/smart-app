@extends('layouts.dashboard')

@section('title', 'Create Approval Request')
@section('header_title', 'Create Approval')
@section('header_subtitle', 'New approval request')

@section('content')
    <div class="container-fluid py-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <form action="{{ route('manager.approvals.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type</label>
                    <input class="form-control" name="type" type="text" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason</label>
                    <input class="form-control" name="reason" type="text">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Payload (JSON)</label>
                    <textarea class="form-control" name="payload" rows="6" placeholder='{"order_id":123}'></textarea>
                </div>
                <button class="btn btn-primary">Create</button>
            </form>
        </div>
    </div>
@endsection
