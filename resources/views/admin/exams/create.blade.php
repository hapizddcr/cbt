@extends('layouts.admin')
@section('title', 'Buat Ujian')
@section('content')
<h2>Buat Ujian Baru</h2>
<form method="POST" action="{{ route('admin.exams.store') }}">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Judul Ujian</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select">
                        <option value="daily">Harian</option><option value="midterm">PTS</option><option value="final">PAS</option><option value="tryout">Tryout</option><option value="quiz" selected>Kuis</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durasi (menit)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="60" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nilai Maks</label>
                    <input type="number" step="0.01" name="max_score" class="form-control" value="100" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nilai Lulus</label>
                    <input type="number" step="0.01" name="passing_score" class="form-control" value="75" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Maks Percobaan</label>
                    <input type="number" name="max_attempts" class="form-control" value="1" min="1" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                </div>
            </div>
            <div class="row g-2 mt-3">
                <div class="col-auto"><label class="form-check"><input type="checkbox" name="shuffle_questions" value="1" checked class="form-check-input"> Acak Soal</label></div>
                <div class="col-auto"><label class="form-check"><input type="checkbox" name="shuffle_options" value="1" checked class="form-check-input"> Acak Opsi</label></div>
                <div class="col-auto"><label class="form-check"><input type="checkbox" name="show_result" value="1" checked class="form-check-input"> Tampilkan Nilai</label></div>
                <div class="col-auto"><label class="form-check"><input type="checkbox" name="show_answer" value="1" class="form-check-input"> Tampilkan Kunci</label></div>
                <div class="col-auto"><label class="form-check"><input type="checkbox" name="allow_review" value="1" checked class="form-check-input"> Boleh Review</label></div>
                <div class="col-auto"><label class="form-check"><input type="checkbox" name="is_active" value="1" checked class="form-check-input"> Aktif</label></div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
