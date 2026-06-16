@extends('layouts.admin')
@section('title', 'Analisis Butir Soal')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-graph-up"></i> Analisis Butir Soal</h2>
    <span class="text-muted small">Item Analysis (CTT)</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Ujian</th>
                        <th>Mata Pelajaran</th>
                        <th>Waktu Mulai</th>
                        <th>Submissions</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $i => $exam)
                    <tr>
                        <td>{{ $exams->firstItem() + $i }}</td>
                        <td><strong>{{ $exam->title }}</strong></td>
                        <td>{{ $exam->subject->name ?? '-' }}</td>
                        <td>{{ $exam->start_time?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $exam->submitted_attempts_count ?? 0 }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.analysis.show', $exam) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-bar-chart"></i> Analisis
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-muted text-center py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Belum ada ujian dengan submission siswa. Analisis baru tersedia setelah siswa menyelesaikan ujian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $exams->links() }}</div>
@endsection
