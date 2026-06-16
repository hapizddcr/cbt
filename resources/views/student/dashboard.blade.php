@extends('layouts.student')
@section('title', 'Dashboard Siswa')
@section('content')
<h2>Halo, {{ auth()->user()->name }}!</h2>
<p class="text-muted">Selamat datang di Computer Based Test.</p>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <i class="bi bi-shield-lock display-1 text-primary"></i>
                <h3 class="mt-3">Mulai Ujian</h3>
                <p class="text-muted">Masukkan token yang diberikan pengawas</p>
                <a href="{{ route('student.exam.token') }}" class="btn btn-primary btn-lg">Input Token</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Ujian Berlangsung</strong></div>
            <div class="card-body">
                @forelse($ongoingExams as $a)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>{{ $a->exam->title }}</strong>
                            <br><small class="text-muted">Sisa: {{ $a->ends_at->diffForHumans() }}</small>
                        </div>
                        <a href="{{ route('student.exam.take', $a) }}" class="btn btn-warning btn-sm">Lanjut</a>
                    </div>
                @empty
                    <p class="text-muted text-center py-3">Tidak ada ujian berlangsung.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white"><strong>Riwayat Nilai</strong></div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Ujian</th><th>Tanggal</th><th>Nilai</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentResults as $a)
                <tr>
                    <td>{{ $a->exam->title }}</td>
                    <td>{{ $a->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td><strong>{{ $a->score ?? 0 }}</strong> / {{ $a->exam->max_score }}</td>
                    <td>{!! $a->is_passed ? '<span class="badge bg-success">Lulus</span>' : '<span class="badge bg-danger">Gagal</span>' !!}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted text-center py-3">Belum ada riwayat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
