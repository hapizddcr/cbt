@extends('layouts.admin')
@section('title', 'Siswa')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Data Siswa</h2>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Siswa</a>
</div>
<form method="GET" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama / NISN / NIS..." value="{{ request('search') }}">
        <button class="btn btn-outline-primary">Cari</button>
    </div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>NISN</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                <tr>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->gender }}</td>
                    <td>{{ $s->classrooms->pluck('name')->join(', ') ?: '-' }}</td>
                    <td>
                        <a href="{{ route('admin.students.show', $s) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                        <a href="{{ route('admin.students.edit', $s) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data siswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $students->links() }}</div>
@endsection
