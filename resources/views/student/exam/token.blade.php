@extends('layouts.exam')
@section('title', 'Masukkan Token Ujian')
@section('content')
<div class="container py-5" style="max-width: 500px;">
    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">
            <i class="bi bi-shield-lock display-1 text-primary"></i>
            <h3 class="mt-3">Mulai Ujian</h3>
            <p class="text-muted">Masukkan token yang diberikan pengawas</p>
            <form method="POST" action="{{ route('student.exam.start') }}">
                @csrf
                <input type="text" name="token" class="form-control form-control-lg text-center fw-bold @error('token') is-invalid @enderror"
                       placeholder="TOKEN" required autofocus style="letter-spacing: 4px; font-size: 24px; text-transform: uppercase;"
                       value="{{ old('token') }}">
                @error('token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if(session('error'))<div class="alert alert-danger mt-3">{{ session('error') }}</div>@endif
                <button class="btn btn-primary btn-lg w-100 mt-3" type="submit">Mulai Ujian</button>
            </form>
        </div>
    </div>
</div>
@endsection
