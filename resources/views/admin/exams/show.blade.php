@extends('layouts.admin')
@section('title', $exam->title)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h2>{{ $exam->title }}</h2>
        <p class="text-muted">{{ $exam->subject->name }} · {{ ucfirst($exam->type) }} · {{ $exam->duration_minutes }} menit</p>
    </div>
    <div>
        <a href="{{ route('admin.sessions.create', ['exam_id' => $exam->id]) }}" class="btn btn-success">+ Sesi</a>
        <a href="{{ route('admin.exams.questions', $exam) }}" class="btn btn-primary">Kelola Soal</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Total Soal</small><h3>{{ $exam->total_questions }}</h3></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Nilai Maks</small><h3>{{ $exam->max_score }}</h3></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Sesi</small><h3>{{ $exam->sessions->count() }}</h3></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Status</small><h3>{!! $exam->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Off</span>' !!}</h3></div></div>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white"><strong>Sesi Ujian</strong></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Mulai</th><th>Selesai</th><th>Ruang</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($exam->sessions as $s)
                <tr>
                    <td><a href="{{ route('admin.sessions.show', $s) }}">{{ $s->name }}</a></td>
                    <td>{{ $s->start_time->format('d M Y H:i') }}</td>
                    <td>{{ $s->end_time->format('d M Y H:i') }}</td>
                    <td>{{ $s->rooms->count() }} ruang</td>
                    <td>{!! $s->isOngoing() ? '<span class="badge bg-success">Berlangsung</span>' : '<span class="badge bg-secondary">Terjadwal</span>' !!}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada sesi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
