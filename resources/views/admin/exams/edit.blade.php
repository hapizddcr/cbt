@extends('layouts.admin')
@section('title', 'Edit Ujian')
@section('content')
<h2>Edit Ujian</h2>
<form method="POST" action="{{ route('admin.exams.update', $exam) }}">
    @csrf @method('PUT')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Judul Ujian</label>
                    <input type="text" name="title" class="form-control" value="{{ $exam->title }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select" required>
                        @foreach($subjects as $s)<option value="{{ $s->id }}" {{ $exam->subject_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select">
                        <option value="daily" {{ $exam->type == 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="midterm" {{ $exam->type == 'midterm' ? 'selected' : '' }}>PTS</option>
                        <option value="final" {{ $exam->type == 'final' ? 'selected' : '' }}>PAS</option>
                        <option value="tryout" {{ $exam->type == 'tryout' ? 'selected' : '' }}>Tryout</option>
                        <option value="quiz" {{ $exam->type == 'quiz' ? 'selected' : '' }}>Kuis</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Durasi (menit)</label><input type="number" name="duration_minutes" class="form-control" value="{{ $exam->duration_minutes }}" required></div>
                <div class="col-md-2"><label class="form-label">Nilai Maks</label><input type="number" step="0.01" name="max_score" class="form-control" value="{{ $exam->max_score }}" required></div>
                <div class="col-md-2"><label class="form-label">Nilai Lulus</label><input type="number" step="0.01" name="passing_score" class="form-control" value="{{ $exam->passing_score }}" required></div>
                <div class="col-md-2"><label class="form-label">Maks Percobaan</label><input type="number" name="max_attempts" class="form-control" value="{{ $exam->max_attempts }}" required></div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
