@extends('layouts.exam')
@section('title', 'Hasil Ujian')
@section('content')
<div class="container py-4">
    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">
            <i class="bi bi-check-circle display-1 text-success"></i>
            <h2 class="mt-3">Ujian Selesai</h2>
            <h1 class="display-3 fw-bold text-primary">{{ number_format($attempt->score ?? 0, 0) }} <small class="text-muted fs-5">/ {{ $exam->max_score }}</small></h1>
            @if($attempt->is_passed)
                <span class="badge bg-success fs-5">LULUS</span>
            @else
                <span class="badge bg-danger fs-5">TIDAK LULUS</span>
            @endif
            <p class="text-muted mt-3">Nilai minimum kelulusan: {{ $exam->passing_score }}</p>
            <a href="{{ route('student.dashboard') }}" class="btn btn-primary mt-3">Kembali ke Dashboard</a>
        </div>
    </div>
</div>
@endsection
