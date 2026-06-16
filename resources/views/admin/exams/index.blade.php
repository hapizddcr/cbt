@extends('layouts.admin')
@section('title', 'Ujian')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Daftar Ujian</h2>
    <a href="{{ route('admin.exams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Ujian</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Judul</th><th>Mapel</th><th>Tipe</th><th>Durasi</th><th>Soal</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($exams as $i => $e)
                <tr>
                    <td>{{ $exams->firstItem() + $i }}</td>
                    <td><strong>{{ $e->title }}</strong></td>
                    <td>{{ $e->subject->name ?? '-' }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($e->type) }}</span></td>
                    <td>{{ $e->duration_minutes }} m</td>
                    <td>{{ $e->total_questions }}</td>
                    <td>{!! $e->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non-aktif</span>' !!}</td>
                    <td>
                        <a href="{{ route('admin.exams.show', $e) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                        <a href="{{ route('admin.sessions.create', ['exam_id' => $e->id]) }}" class="btn btn-sm btn-outline-success">+ Sesi</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada ujian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $exams->links() }}</div>
@endsection
