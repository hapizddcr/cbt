@extends('layouts.admin')
@section('title', 'Koreksi Ujian')
@section('content')
<h2>Koreksi Ujian</h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Siswa</th><th>Ujian</th><th>Waktu Submit</th><th>Status</th><th>Nilai</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($attempts as $a)
                <tr>
                    <td>{{ $a->student->name ?? '-' }}</td>
                    <td>{{ $a->exam->title ?? '-' }}</td>
                    <td>{{ $a->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>{!! $a->status === 'graded' ? '<span class="badge bg-success">Selesai</span>' : '<span class="badge bg-warning">Perlu Koreksi</span>' !!}</td>
                    <td><strong>{{ $a->score ?? '0' }}</strong></td>
                    <td><a href="{{ route('admin.grading.show', $a) }}" class="btn btn-sm btn-outline-primary">Koreksi</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted text-center py-3">Belum ada submission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $attempts->links() }}</div>
@endsection
