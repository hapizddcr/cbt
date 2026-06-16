@extends('layouts.admin')
@section('title', 'Sesi & Token')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Sesi Ujian</h2>
    <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Sesi</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Ujian</th><th>Sesi</th><th>Mulai</th><th>Selesai</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($sessions as $s)
                <tr>
                    <td>{{ $s->exam->title ?? '-' }}</td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->start_time->format('d M Y H:i') }}</td>
                    <td>{{ $s->end_time->format('d M Y H:i') }}</td>
                    <td>{!! $s->isOngoing() ? '<span class="badge bg-success">Berlangsung</span>' : '<span class="badge bg-secondary">Terjadwal</span>' !!}</td>
                    <td><a href="{{ route('admin.sessions.show', $s) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted text-center py-3">Belum ada sesi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sessions->links() }}</div>
@endsection
