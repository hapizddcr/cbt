@extends('layouts.admin')
@section('title', 'Bank Soal')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Bank Soal</h2>
    <a href="{{ route('admin.questions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Soal</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="bank_id" class="form-select">
            <option value="">Semua Bank Soal</option>
            @foreach($banks as $b)<option value="{{ $b->id }}" {{ request('bank_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="type" class="form-select">
            <option value="">Semua Tipe</option>
            @foreach(\App\Models\Question::TYPES as $key => $label)<option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div class="col-auto"><input type="text" name="search" class="form-control" placeholder="Cari soal..." value="{{ request('search') }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Soal</th><th>Tipe</th><th>Bank</th><th>Skor</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($questions as $i => $q)
                <tr>
                    <td>{{ $questions->firstItem() + $i }}</td>
                    <td>{{ Str::limit(strip_tags($q->content), 80) }}</td>
                    <td><span class="badge bg-secondary">{{ $q->type_label }}</span></td>
                    <td>{{ $q->questionBank->name ?? '-' }}</td>
                    <td>{{ $q->default_score }}</td>
                    <td>
                        <a href="{{ route('admin.questions.edit', $q) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('admin.questions.destroy', $q) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus soal?')">Hapus</button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada soal. <a href="{{ route('admin.questions.create') }}">Tambah soal pertama</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $questions->links() }}</div>
@endsection
