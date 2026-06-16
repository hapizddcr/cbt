@extends('layouts.admin')
@section('title', 'Tambah Siswa')
@section('content')
<h2>Tambah Siswa</h2>
<form method="POST" action="{{ route('admin.students.store') }}">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">NISN</label><input type="text" name="nisn" class="form-control @error('nisn') is-invalid @enderror" required></div>
                <div class="col-md-3"><label class="form-label">NIS</label><input type="text" name="nis" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Jenis Kelamin</label><select name="gender" class="form-select"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                <div class="col-md-5"><label class="form-label">Tempat Lahir</label><input type="text" name="birth_place" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Tanggal Lahir</label><input type="date" name="birth_date" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Email (untuk login)</label><input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required></div>
                <div class="col-md-6"><label class="form-label">Password Awal</label><input type="text" name="password" class="form-control" value="password" required></div>
                <div class="col-md-6"><label class="form-label">Kelas</label><select name="classroom_id" class="form-select"><option value="">-- Pilih --</option>@foreach($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">No. HP</label><input type="text" name="phone" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Nama Orang Tua</label><input type="text" name="parent_name" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">HP Orang Tua</label><input type="text" name="parent_phone" class="form-control"></div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
