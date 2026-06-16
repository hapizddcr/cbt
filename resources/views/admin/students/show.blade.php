@extends('layouts.admin')
@section('title', $student->name)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h2>{{ $student->name }}</h2>
        <p class="text-muted">NISN: {{ $student->nisn }} · {{ $student->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
    </div>
    <div>
        <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3">
            <h5>Informasi</h5>
            <table class="table table-borderless">
                <tr><th>NIS</th><td>{{ $student->nis ?? '-' }}</td></tr>
                <tr><th>TTL</th><td>{{ $student->birth_place ?? '-' }}, {{ $student->birth_date?->format('d M Y') ?? '-' }}</td></tr>
                <tr><th>Kelas</th><td>{{ $student->classrooms->pluck('name')->join(', ') ?: '-' }}</td></tr>
                <tr><th>HP</th><td>{{ $student->phone ?? '-' }}</td></tr>
                <tr><th>Email</th><td>{{ $student->user->email }}</td></tr>
                <tr><th>Orang Tua</th><td>{{ $student->parent_name ?? '-' }} ({{ $student->parent_phone ?? '-' }})</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Riwayat Ujian</strong></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Ujian</th><th>Nilai</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($student->examAttempts as $a)
                        <tr>
                            <td>{{ $a->exam->title }}</td>
                            <td>{{ $a->score ?? 0 }}</td>
                            <td>{!! $a->is_passed ? '<span class="badge bg-success">Lulus</span>' : '<span class="badge bg-danger">Gagal</span>' !!}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">Belum pernah ikut ujian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
