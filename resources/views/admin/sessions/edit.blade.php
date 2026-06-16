@extends('layouts.admin')
@section('title', 'Edit Sesi')
@section('content')
<h2>Edit Sesi</h2>
<form method="POST" action="{{ route('admin.sessions.update', $session) }}">
    @csrf @method('PUT')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" value="{{ $session->name }}" required></div>
                <div class="col-md-3"><label class="form-label">Durasi (menit)</label><input type="number" name="duration_minutes" class="form-control" value="{{ $session->duration_minutes }}" required></div>
                <div class="col-md-3"><label class="form-label">Token Lifetime</label><input type="number" name="token_lifetime_minutes" class="form-control" value="{{ $session->token_lifetime_minutes }}" required></div>
                <div class="col-md-6"><label class="form-label">Mulai</label><input type="datetime-local" name="start_time" class="form-control" value="{{ $session->start_time->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-md-6"><label class="form-label">Selesai</label><input type="datetime-local" name="end_time" class="form-control" value="{{ $session->end_time->format('Y-m-d\TH:i') }}" required></div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
