@extends('layouts.admin')
@section('title', 'Mata Pelajaran')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Mata Pelajaran</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubject">+ Tambah</button>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Kelompok</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($subjects as $s)
                <tr>
                    <td>{{ $s->code }}</td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->group }}</td>
                    <td>{!! $s->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Off</span>' !!}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.master.subjects.destroy', $s) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">×</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addSubject">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.master.subjects.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>Tambah Mata Pelajaran</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Kode</label>
                <input type="text" name="code" class="form-control mb-2" required>
                <label class="form-label">Nama</label>
                <input type="text" name="name" class="form-control mb-2" required>
                <label class="form-label">Kelompok</label>
                <input type="text" name="group" class="form-control">
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
