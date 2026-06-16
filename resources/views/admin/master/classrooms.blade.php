@extends('layouts.admin')
@section('title', 'Kelas')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Kelas</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClass">+ Tambah</button>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Tingkat</th><th>Jurusan</th><th>Kapasitas</th><th>Siswa</th><th>T.Ajaran</th></tr></thead>
            <tbody>
                @foreach($classrooms as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->grade }}</td>
                    <td>{{ $c->major }}</td>
                    <td>{{ $c->capacity }}</td>
                    <td>{{ $c->students->count() }}</td>
                    <td>{{ $c->academicYear->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="addClass">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.master.classrooms.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>Tambah Kelas</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Tahun Ajaran</label>
                <select name="academic_year_id" class="form-select mb-2" required>
                    @foreach($academicYears as $y)<option value="{{ $y->id }}">{{ $y->name }} - {{ $y->semester }}</option>@endforeach
                </select>
                <label class="form-label">Nama</label>
                <input type="text" name="name" class="form-control mb-2" required>
                <label class="form-label">Tingkat</label>
                <input type="text" name="grade" class="form-control mb-2" required>
                <label class="form-label">Jurusan</label>
                <input type="text" name="major" class="form-control mb-2">
                <label class="form-label">Kapasitas</label>
                <input type="number" name="capacity" class="form-control" value="36" required>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
