@extends('layouts.admin')
@section('title', 'Edit Siswa')
@section('content')
<h2>Edit Siswa: {{ $student->name }}</h2>
<form method="POST" action="{{ route('admin.students.update', $student) }}">
    @csrf @method('PUT')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">NISN</label><input type="text" name="nisn" class="form-control" value="{{ $student->nisn }}" required></div>
                <div class="col-md-3"><label class="form-label">NIS</label><input type="text" name="nis" class="form-control" value="{{ $student->nis }}"></div>
                <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" value="{{ $student->name }}" required></div>
                <div class="col-md-3"><label class="form-label">Jenis Kelamin</label><select name="gender" class="form-select"><option value="L" {{ $student->gender == 'L' ? 'selected' : '' }}>Laki-laki</option><option value="P" {{ $student->gender == 'P' ? 'selected' : '' }}>Perempuan</option></select></div>
                <div class="col-md-9"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $student->user->email }}" required></div>
                <div class="col-md-6"><label class="form-label">Kelas</label><select name="classroom_id" class="form-select"><option value="">-- Pilih --</option>@foreach($classrooms as $c)<option value="{{ $c->id }}" {{ $student->classrooms->contains($c->id) ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.students.show', $student) }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
