@extends('layouts.admin')
@section('title', 'Tahun Ajaran')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Tahun Ajaran</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addYear">+ Tambah</button>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Tahun</th><th>Semester</th><th>Periode</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($academicYears as $y)
                <tr>
                    <td>{{ $y->name }}</td>
                    <td>{{ $y->semester }}</td>
                    <td>{{ $y->start_date->format('d M Y') }} - {{ $y->end_date->format('d M Y') }}</td>
                    <td>{!! $y->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Off</span>' !!}</td>
                    <td>@if(!$y->is_active)<form method="POST" action="{{ route('admin.master.academic-years.activate', $y) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Aktifkan</button></form>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="addYear">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.master.academic-years.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>Tambah Tahun Ajaran</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Nama</label>
                <input type="text" name="name" class="form-control mb-2" placeholder="2024/2025" required>
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select mb-2" required>
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>
                <label class="form-label">Mulai</label>
                <input type="date" name="start_date" class="form-control mb-2" required>
                <label class="form-label">Selesai</label>
                <input type="date" name="end_date" class="form-control mb-2" required>
                <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active"><label class="form-check-label" for="active">Aktifkan</label></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
