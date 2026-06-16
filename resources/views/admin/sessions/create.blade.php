@extends('layouts.admin')
@section('title', 'Buat Sesi')
@section('content')
<h2>Buat Sesi Ujian</h2>
<form method="POST" action="{{ route('admin.sessions.store') }}">
    @csrf
    <input type="hidden" name="exam_id" value="{{ $examId }}">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Sesi</label>
                    <input type="text" name="name" class="form-control" placeholder="Sesi 1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durasi (menit)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="60" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Token Lifetime (menit)</label>
                    <input type="number" name="token_lifetime_minutes" class="form-control" value="60" min="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="datetime-local" name="end_time" class="form-control" required>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.sessions.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
