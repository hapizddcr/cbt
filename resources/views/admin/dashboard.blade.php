@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Siswa</h6>
                <h2 class="mb-0">{{ $stats['students'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Mata Pelajaran</h6>
                <h2 class="mb-0">{{ $stats['subjects'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Ujian</h6>
                <h2 class="mb-0">{{ $stats['exams'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Tahun Ajaran</h6>
                <h2 class="mb-0">{{ $stats['academic_years'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Ujian Terbaru</h5>
            </div>
            <div class="card-body">
                @forelse($recentExams as $exam)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>{{ $exam->title }}</strong>
                            <br><small class="text-muted">{{ $exam->subject->name ?? '-' }} · {{ ucfirst($exam->type) }}</small>
                        </div>
                        <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </div>
                @empty
                    <p class="text-muted">Belum ada ujian.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Pengumuman</h5>
            </div>
            <div class="card-body">
                @forelse($announcements as $a)
                    <div class="py-2 border-bottom">
                        <strong>{{ $a->title }}</strong>
                        <p class="small text-muted mb-0">{{ Str::limit($a->content, 80) }}</p>
                    </div>
                @empty
                    <p class="text-muted">Belum ada pengumuman.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
